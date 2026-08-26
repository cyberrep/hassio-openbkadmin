<?php

/**
 * OpenBKAdmin 0.5.1 discovery override.
 *
 * Uses a more tolerant two-pass TCP scan and, when at least one OpenBeken
 * device is found, also consumes OpenBeken's native /obkdevicelist endpoint.
 * That endpoint is backed by the SSDP obkDeviceList implementation and can
 * reveal peers that did not answer the first active scan quickly enough.
 */
if (!function_exists('scanOpenBekenDevices')) {
    function scanOpenBekenDevices(
        array $ips,
        int $httpPort,
        string $username,
        string $password,
        array $skippedAddresses,
        \OpenBKAdmin\OpenBeken $OpenBeken
    ): array {
        $allowed = array_fill_keys($ips, true);
        $candidates = [];

        $probe = static function (string $ip, float $timeout) use ($httpPort, $skippedAddresses): bool {
            $address = sprintf('%s:%d', $ip, $httpPort);
            if (in_array($address, $skippedAddresses, true)) {
                return false;
            }
            $errno = 0;
            $errstr = '';
            $socket = @fsockopen($ip, $httpPort, $errno, $errstr, $timeout);
            if (!is_resource($socket)) {
                return false;
            }
            fclose($socket);
            return true;
        };

        // Two passes: the second, slightly slower pass catches busy/recovering IoT nodes.
        foreach ($ips as $ip) {
            if ($probe($ip, 0.22)) {
                $candidates[$ip] = true;
            }
        }
        foreach ($ips as $ip) {
            if (!isset($candidates[$ip]) && $probe($ip, 0.45)) {
                $candidates[$ip] = true;
            }
        }

        $results = [];
        $inspect = static function (string $ip) use ($httpPort, $username, $password, $OpenBeken, &$results): bool {
            if (isset($results[(string) ip2long($ip)])) {
                return true;
            }
            $device = \OpenBKAdmin\DeviceFactory::fakeDevice($ip, $httpPort, $username, $password);
            if (!$OpenBeken->isOpenBeken($device)) {
                return false;
            }
            $status = $OpenBeken->getOpenBekenStatus($device);
            if (isset($status->ERROR) || empty($status->StatusNET->IPAddress)) {
                return false;
            }
            $nativeNames = $OpenBeken->getOpenBekenNativeNames($device);
            if (!isset($status->OpenBeken) || !is_object($status->OpenBeken)) {
                $status->OpenBeken = new \stdClass();
            }
            $status->OpenBeken->FullName = $nativeNames->FullName;
            $status->OpenBeken->ShortName = $nativeNames->ShortName;
            $status->OpenBKAdminDevicePort = $httpPort;
            $results[(string) ip2long($ip)] = $status;
            return true;
        };

        foreach (array_keys($candidates) as $ip) {
            $inspect($ip);
        }

        // Ask discovered OpenBeken nodes for their native SSDP peer list.
        // /obkdevicelist returns JSON such as [{"ip":"192.168.1.20"}, ...].
        $peerIps = [];
        foreach ($results as $status) {
            $seedIp = (string) ($status->StatusNET->IPAddress ?? '');
            if ($seedIp === '') {
                continue;
            }
            $context = stream_context_create(['http' => ['timeout' => 1.2, 'ignore_errors' => true]]);
            $json = @file_get_contents(sprintf('http://%s:%d/obkdevicelist', $seedIp, $httpPort), false, $context);
            if (!is_string($json) || $json === '') {
                continue;
            }
            $peers = json_decode($json, true);
            if (!is_array($peers)) {
                continue;
            }
            foreach ($peers as $peer) {
                $peerIp = is_array($peer) ? (string) ($peer['ip'] ?? '') : '';
                if (filter_var($peerIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) && isset($allowed[$peerIp])) {
                    $peerIps[$peerIp] = true;
                }
            }
        }

        foreach (array_keys($peerIps) as $ip) {
            if ($probe($ip, 0.65)) {
                $inspect($ip);
            }
        }

        ksort($results, SORT_NUMERIC);
        return array_values($results);
    }
}

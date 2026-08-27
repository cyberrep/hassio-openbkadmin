<?php

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use OpenBKAdmin\Backup\BackupHelper;
use OpenBKAdmin\DevicePasswordKeyProvider;
use OpenBKAdmin\DeviceRepository;
use OpenBKAdmin\Helper\CacheCleanupHelper;
use OpenBKAdmin\Helper\FirmwareFolderHelper;
use OpenBKAdmin\Helper\SupportedLanguageHelper;
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);

if (isset($_GET['removeDevices'], $_GET['ids'])) {
    $deviceRepository = $container->get(DeviceRepository::class);
    $ids = array_map('intval', explode(',', $_GET['ids']));
    $deviceRepository->removeDevices($ids);
    exit;
}

if (isset($_GET['doAjax'])) {
    session_write_close();
    if (isset($_REQUEST['target'])) {
        $data = $OpenBeken->setDeviceValue((int) $_REQUEST['id'], $_REQUEST['field'], $_REQUEST['newvalue']);
    } else {
        $data = $OpenBeken->doAjax($_REQUEST['id'], urldecode($_REQUEST['cmnd']));
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if (isset($_GET['doAjaxAll'])) {
    session_write_close();
    $data = $OpenBeken->doAjaxAll();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Native OpenBeken Web App OTA proxy for BL602/BL616.
// The OpenBeken REST implementation consumes the complete .ota image as the
// raw request body of POST /api/ota. Firmware already downloaded by
// OpenBKAdmin is read directly from /data/firmwares instead of making an HTTP
// request back to the add-on itself. This also matches what the browser Web App
// does: the actual OTA bytes, not the URL, are posted to the device.
if (isset($_GET['nativeOta'])) {
    session_write_close();
    header('Content-Type: application/json');

    $deviceId = (int) ($_REQUEST['id'] ?? 0);
    $firmwareUrl = trim((string) ($_REQUEST['url'] ?? ''));
    $device = $OpenBeken->getDeviceById($deviceId);

    if (null === $device || '' === $firmwareUrl) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Invalid device or firmware URL']);
        exit;
    }

    $scheme = strtolower((string) parse_url($firmwareUrl, PHP_URL_SCHEME));
    if (!in_array($scheme, ['http', 'https'], true)) {
        http_response_code(400);
        echo json_encode(['ERROR' => 'Firmware URL must use HTTP or HTTPS']);
        exit;
    }

    $client = new Client(['timeout' => 180, 'connect_timeout' => 10, 'http_errors' => true]);
    $tmp = null;
    $firmwareSource = 'remote';

    try {
        // Automatic OTA firmware is already stored here by FirmwareDownloader.
        // Prefer the local copy. Calling the add-on's public URL from inside its
        // own container can return an empty/short response depending on ingress,
        // authentication and host routing, which caused the previous <512-byte
        // "Firmware file is too small or invalid" failure.
        $urlPath = (string) parse_url($firmwareUrl, PHP_URL_PATH);
        $firmwareName = basename(rawurldecode($urlPath));
        $localFirmware = _DATADIR_.'firmwares/'.$firmwareName;

        if ('' !== $firmwareName && is_file($localFirmware) && is_readable($localFirmware)) {
            $size = filesize($localFirmware);
            if (false === $size || $size < 512) {
                throw new RuntimeException('Local firmware file is too small or invalid: '.$firmwareName);
            }
            $tmp = fopen($localFirmware, 'rb');
            if (false === $tmp) throw new RuntimeException('Could not open local OTA firmware');
            $firmwareSource = 'local';
        } else {
            $tmp = fopen('php://temp/maxmemory:5242880', 'w+b');
            if (false === $tmp) throw new RuntimeException('Could not create temporary OTA stream');

            $downloadResponse = $client->request('GET', $firmwareUrl, [
                'sink' => $tmp,
                'allow_redirects' => true,
            ]);
            if ($downloadResponse->getStatusCode() < 200 || $downloadResponse->getStatusCode() >= 300) {
                throw new RuntimeException('Firmware download failed with HTTP '.$downloadResponse->getStatusCode());
            }
            $stats = fstat($tmp);
            $size = (int) ($stats['size'] ?? 0);
            if ($size < 512) throw new RuntimeException('Downloaded firmware file is too small or invalid ('.$size.' bytes)');
            rewind($tmp);
        }

        // BL602/BL616 OpenBeken OTA starts with a 512-byte Bouffalo OTA header.
        $header = fread($tmp, 16);
        if (false === $header || 0 !== strncmp($header, 'BL60X_OTA', 9)) {
            $hex = false === $header ? '' : strtoupper(bin2hex(substr($header, 0, 12)));
            throw new RuntimeException('Invalid BL602 OTA header: expected BL60X_OTA'.($hex !== '' ? ' (got '.$hex.')' : ''));
        }
        rewind($tmp);

        $options = [
            'body' => $tmp,
            'headers' => [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) $size,
                'Expect' => '',
                'Connection' => 'close',
            ],
            'expect' => false,
            'timeout' => 180,
            'connect_timeout' => 10,
        ];
        if (!empty($device->password)) $options[RequestOptions::AUTH] = [$device->username, $device->password];

        $baseDeviceUrl = sprintf('http://%s:%s', $device->ip, $device->port);
        $response = $client->request('POST', $baseDeviceUrl.'/api/ota', $options);
        $body = trim((string) $response->getBody());
        $decoded = json_decode($body, true);

        if (!is_array($decoded) || !isset($decoded['size'])) {
            throw new RuntimeException('OpenBeken OTA did not confirm bytes written. Response: '.substr($body, 0, 250));
        }
        $written = (int) $decoded['size'];
        if ($written !== (int) $size) {
            throw new RuntimeException(sprintf('OpenBeken OTA size mismatch: sent %d bytes, device confirmed %d bytes', $size, $written));
        }

        if (is_resource($tmp)) { fclose($tmp); $tmp = null; }

        // The BL602 writer updates the boot partition table after validating the
        // SHA256. Request reboot only after the device confirms the full image.
        $rebootRequested = false;
        try {
            $rebootOptions = [
                'body' => '',
                'headers' => ['Content-Length' => '0', 'Expect' => '', 'Connection' => 'close'],
                'expect' => false,
                'timeout' => 5,
                'connect_timeout' => 3,
                'http_errors' => false,
            ];
            if (!empty($device->password)) $rebootOptions[RequestOptions::AUTH] = [$device->username, $device->password];
            $client->request('POST', $baseDeviceUrl.'/api/reboot', $rebootOptions);
            $rebootRequested = true;
        } catch (Throwable $ignored) {
            // Connection loss is expected if reboot begins immediately.
            $rebootRequested = true;
        }

        echo json_encode([
            'success' => true,
            'status' => $response->getStatusCode(),
            'device' => $deviceId,
            'source' => $firmwareSource,
            'file' => $firmwareName,
            'size' => $size,
            'written' => $written,
            'rebootRequested' => $rebootRequested,
            'response' => $decoded,
        ]);
    } catch (GuzzleException|RuntimeException $exception) {
        if (is_resource($tmp)) fclose($tmp);
        http_response_code(502);
        echo json_encode(['ERROR' => $exception->getMessage(), 'source' => $firmwareSource]);
    }
    exit;
}

if (isset($_GET['i18n'])) {
    $requestedLang = $_GET['lang'] ?? $lang;
    $supportedLanguages = SupportedLanguageHelper::getSupportedLanguages();
    $language = array_key_exists($requestedLang, $supportedLanguages) ? $requestedLang : $lang;
    $cacheFile = _TMPDIR_.'cache/i18n/json_i18n_'.$language.'.cache.json';
    if (!is_file($cacheFile)) {
        http_response_code(404); header('Content-Type: application/json'); echo json_encode(['error' => 'Language cache not found']); exit;
    }
    header('Content-Type: application/json'); readfile($cacheFile); exit;
}

if (isset($_GET['downloadBackup'])) {
    $backup = $container->get(BackupHelper::class);
    header('Content-type: application/zip');
    header('Content-Disposition: attachment; filename="OpenBeken-backup.zip"');
    header('Content-Length: '.filesize($backup->getBackupZipPath()));
    ob_clean(); flush(); readfile($backup->getBackupZipPath()); exit;
}

if (isset($_GET['downloadRestore'])) {
    $backup = $container->get(BackupHelper::class);
    $restoreToken = (string) $_GET['downloadRestore'];
    $restorePath = $backup->getRestoreFilePath($restoreToken);
    if (null === $restorePath) { http_response_code(404); exit; }
    header('Content-type: application/octet-stream');
    header('Content-Disposition: attachment; filename="restore.dmp"');
    header('Content-Length: '.filesize($restorePath));
    ob_clean(); flush(); readfile($restorePath); $backup->deleteRestoreFile($restoreToken); exit;
}

if (isset($_GET['clean'])) {
    $what = explode('_', $_GET['clean']);
    if (array_intersect(['sessions', 'i18n'], $what)) CacheCleanupHelper::cleanTargets(_TMPDIR_, $what);
    if (in_array('firmwares', $what)) FirmwareFolderHelper::clean(_DATADIR_.'firmwares/');
    if (in_array('config', $what)) {
        foreach (glob(_DATADIR_.'/*') as $file) if (is_file($file) && (strpos($file, 'MyConfig.json') || strpos($file, 'MyConfig.php'))) @unlink($file);
        session_destroy();
    }
    if (in_array('devices', $what)) {
        foreach (glob(_DATADIR_.'/*') as $file) if (is_file($file) && strpos($file, 'devices.csv')) @unlink($file);
        $devicePasswordKeyFile = _DATADIR_.DevicePasswordKeyProvider::SIDECAR_FILENAME;
        if (is_file($devicePasswordKeyFile)) @unlink($devicePasswordKeyFile);
    }
    exit;
}

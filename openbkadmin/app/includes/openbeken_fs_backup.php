<?php

use OpenBKAdmin\Device;

/**
 * Download the OpenBeken LittleFS through its native REST API and build a TAR.
 * Upstream exposes directory/file reads at GET /api/lfs/<path>.
 */
function obkFsHttpGet(Device $device, string $path): string
{
    $segments = array_values(array_filter(explode('/', trim($path, '/')), static fn($v) => $v !== ''));
    $encoded = implode('/', array_map('rawurlencode', $segments));
    $url = 'http://'.$device->getAddress().'/api/lfs/'.$encoded;

    $ch = curl_init($url);
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_FAILONERROR => false,
    ];
    if ($device->password !== '') {
        $options[CURLOPT_HTTPAUTH] = CURLAUTH_ANY;
        $options[CURLOPT_USERPWD] = $device->username.':'.$device->password;
    }
    curl_setopt_array($ch, $options);
    $body = curl_exec($ch);
    $error = curl_error($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    curl_close($ch);

    if ($body === false || $status < 200 || $status >= 300) {
        throw new RuntimeException('LittleFS GET '.$path.' failed'.($status ? ' (HTTP '.$status.')' : '').($error ? ': '.$error : ''));
    }

    return (string) $body;
}

function obkFsCollect(Device $device, string $dir = ''): array
{
    $body = obkFsHttpGet($device, $dir);
    $listing = json_decode($body, true);
    if (!is_array($listing) || !isset($listing['content']) || !is_array($listing['content'])) {
        throw new RuntimeException('OpenBeken did not return a LittleFS directory listing for /'.$dir);
    }

    $files = [];
    foreach ($listing['content'] as $entry) {
        $name = (string) ($entry['name'] ?? '');
        if ($name === '' || $name === '.' || $name === '..' || isset($entry['error'])) continue;
        $path = ltrim(($dir !== '' ? rtrim($dir, '/').'/' : '').$name, '/');
        $type = (int) ($entry['type'] ?? 0);
        if ($type === 2) {
            $files += obkFsCollect($device, $path);
        } else {
            $files[$path] = obkFsHttpGet($device, $path);
        }
    }
    return $files;
}

function obkTarOctal(int $value, int $length): string
{
    return str_pad(decoct(max(0, $value)), $length - 1, '0', STR_PAD_LEFT)."\0";
}

function obkTarHeader(string $path, int $size, int $mtime): string
{
    $path = str_replace('\\', '/', ltrim($path, '/'));
    $name = $path;
    $prefix = '';
    if (strlen($name) > 100) {
        $cut = strrpos(substr($name, 0, 155), '/');
        if ($cut === false) throw new RuntimeException('LittleFS path is too long for TAR: '.$path);
        $prefix = substr($name, 0, $cut);
        $name = substr($name, $cut + 1);
        if (strlen($name) > 100 || strlen($prefix) > 155) throw new RuntimeException('LittleFS path is too long for TAR: '.$path);
    }

    $header = str_pad($name, 100, "\0");
    $header .= obkTarOctal(0644, 8);
    $header .= obkTarOctal(0, 8);
    $header .= obkTarOctal(0, 8);
    $header .= obkTarOctal($size, 12);
    $header .= obkTarOctal($mtime, 12);
    $header .= str_repeat(' ', 8);
    $header .= '0';
    $header .= str_repeat("\0", 100);
    $header .= "ustar\0";
    $header .= '00';
    $header .= str_pad('openbkadmin', 32, "\0");
    $header .= str_pad('openbkadmin', 32, "\0");
    $header .= obkTarOctal(0, 8);
    $header .= obkTarOctal(0, 8);
    $header .= str_pad($prefix, 155, "\0");
    $header .= str_repeat("\0", 12);
    $header = substr($header, 0, 512);

    $checksum = 0;
    for ($i = 0; $i < 512; $i++) $checksum += ord($header[$i]);
    $checksumField = str_pad(decoct($checksum), 6, '0', STR_PAD_LEFT)."\0 ";
    return substr_replace($header, $checksumField, 148, 8);
}

function obkWriteTar(string $targetPath, array $files): void
{
    $fh = @fopen($targetPath, 'wb');
    if ($fh === false) throw new RuntimeException('Could not open filesystem TAR for writing');
    try {
        $mtime = time();
        foreach ($files as $path => $content) {
            $content = (string) $content;
            $size = strlen($content);
            if (fwrite($fh, obkTarHeader((string) $path, $size, $mtime)) !== 512) throw new RuntimeException('Could not write TAR header for '.$path);
            if ($size > 0 && fwrite($fh, $content) !== $size) throw new RuntimeException('Could not write TAR data for '.$path);
            $padding = (512 - ($size % 512)) % 512;
            if ($padding && fwrite($fh, str_repeat("\0", $padding)) !== $padding) throw new RuntimeException('Could not pad TAR entry '.$path);
        }
        if (fwrite($fh, str_repeat("\0", 1024)) !== 1024) throw new RuntimeException('Could not finalize filesystem TAR');
    } finally {
        fclose($fh);
    }
}

function obkCreateFilesystemTar(Device $device, string $targetPath): array
{
    $files = obkFsCollect($device, '');
    if (empty($files)) throw new RuntimeException('LittleFS is empty or unavailable');

    if (file_exists($targetPath)) @unlink($targetPath);
    try {
        obkWriteTar($targetPath, $files);
    } catch (Throwable $e) {
        @unlink($targetPath);
        throw new RuntimeException('Could not create filesystem TAR: '.$e->getMessage(), 0, $e);
    }

    if (!is_file($targetPath) || filesize($targetPath) <= 1024) {
        @unlink($targetPath);
        throw new RuntimeException('Filesystem TAR was not created');
    }
    return ['file' => $targetPath, 'count' => count($files), 'size' => filesize($targetPath)];
}

/** Keep only the newest N timestamped backup sets for a physical device. */
function obkPruneDeviceBackupSets(string $backupDir, int $deviceId, int $keep = 2): void
{
    $matches = [];
    foreach (glob(rtrim($backupDir, '/').'/*-'.$deviceId.'-*') ?: [] as $path) {
        $base = basename($path);
        if (!preg_match('/^(\d{8}-\d{6})-'.preg_quote((string)$deviceId, '/').'-/', $base, $m)) continue;
        $matches[$m[1]][] = $path;
    }
    krsort($matches, SORT_STRING);
    foreach (array_slice($matches, $keep, null, true) as $paths) {
        foreach ($paths as $path) if (is_file($path)) @unlink($path);
    }
}

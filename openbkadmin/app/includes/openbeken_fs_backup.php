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

function obkCreateFilesystemTar(Device $device, string $targetPath): array
{
    $files = obkFsCollect($device, '');
    if (empty($files)) {
        throw new RuntimeException('LittleFS is empty or unavailable');
    }

    if (file_exists($targetPath)) @unlink($targetPath);
    try {
        $tar = new PharData($targetPath);
        foreach ($files as $path => $content) $tar->addFromString($path, $content);
        unset($tar);
    } catch (Throwable $e) {
        @unlink($targetPath);
        throw new RuntimeException('Could not create filesystem TAR: '.$e->getMessage(), 0, $e);
    }

    if (!is_file($targetPath) || filesize($targetPath) <= 0) {
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

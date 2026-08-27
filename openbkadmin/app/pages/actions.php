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

// Native OpenBeken Web App OTA proxy. BL602/BL616 consume the OTA file as the
// raw HTTP request body at POST /api/ota (not multipart/form-data). The proxy
// is required because the browser cannot reliably POST directly to LAN devices.
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

    try {
        $tmp = fopen('php://temp/maxmemory:5242880', 'w+b');
        if (false === $tmp) throw new RuntimeException('Could not create temporary OTA stream');

        $downloadResponse = $client->request('GET', $firmwareUrl, ['sink' => $tmp]);
        if ($downloadResponse->getStatusCode() < 200 || $downloadResponse->getStatusCode() >= 300) {
            throw new RuntimeException('Firmware download failed with HTTP '.$downloadResponse->getStatusCode());
        }

        $size = ftell($tmp);
        if (false === $size || $size < 512) throw new RuntimeException('Firmware file is too small or invalid');
        rewind($tmp);

        // BL602 OTA files have a 512-byte header whose identifier starts with
        // BL60X_OTA. Rejecting a wrong image here prevents erasing the backup
        // partition only to have OpenBeken reject the header afterwards.
        $header = fread($tmp, 16);
        if (false === $header || 0 !== strncmp($header, 'BL60X_OTA', 9)) {
            throw new RuntimeException('Invalid BL602 OTA header: expected BL60X_OTA');
        }
        rewind($tmp);

        $options = [
            'body' => $tmp,
            'headers' => [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) $size,
                // Guzzle may otherwise use Expect: 100-Continue for a large
                // body. OpenBeken's small embedded HTTP server expects the raw
                // body immediately and does not need that handshake.
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

        // BL602's OTA writer updates the boot partition table but does not call
        // the reboot routine itself. The official REST API exposes /api/reboot,
        // so reboot only after /api/ota has confirmed the complete image.
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
            // A connection drop is normal if the device reboots before sending
            // the complete HTTP response. At this point OTA was already fully
            // validated, so let the version polling determine final success.
            $rebootRequested = true;
        }

        echo json_encode([
            'success' => true,
            'status' => $response->getStatusCode(),
            'device' => $deviceId,
            'size' => $size,
            'written' => $written,
            'rebootRequested' => $rebootRequested,
            'response' => $decoded,
        ]);
    } catch (GuzzleException|RuntimeException $exception) {
        if (is_resource($tmp)) fclose($tmp);
        http_response_code(502);
        echo json_encode(['ERROR' => $exception->getMessage()]);
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

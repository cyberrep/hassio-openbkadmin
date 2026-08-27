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

// BL602/BL616 and other OpenBeken platforms expose the native Web App OTA
// endpoint as POST /api/ota. The browser cannot reliably POST firmware to a
// LAN device because of CORS, so OpenBKAdmin proxies the firmware server-side.
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

    $client = new Client([
        'timeout' => 120,
        'connect_timeout' => 10,
        'http_errors' => true,
    ]);

    try {
        // Download into a temporary stream so large OTA images are not copied
        // into PHP strings more than necessary.
        $tmp = fopen('php://temp/maxmemory:5242880', 'w+b');
        $client->request('GET', $firmwareUrl, ['sink' => $tmp]);
        $size = ftell($tmp);
        rewind($tmp);

        if ($size < 512) {
            throw new RuntimeException('Firmware file is too small or invalid');
        }

        $options = [
            'body' => $tmp,
            'headers' => [
                'Content-Type' => 'application/octet-stream',
                'Content-Length' => (string) $size,
            ],
            'timeout' => 180,
            'connect_timeout' => 10,
        ];

        if (!empty($device->password)) {
            $options[RequestOptions::AUTH] = [$device->username, $device->password];
        }

        $otaEndpoint = sprintf('http://%s:%s/api/ota', $device->ip, $device->port);
        $response = $client->request('POST', $otaEndpoint, $options);
        $body = trim((string) $response->getBody());
        fclose($tmp);

        $decoded = json_decode($body, true);
        echo json_encode([
            'success' => true,
            'status' => $response->getStatusCode(),
            'device' => $deviceId,
            'size' => $size,
            'response' => is_array($decoded) ? $decoded : $body,
        ]);
    } catch (GuzzleException|RuntimeException $exception) {
        if (isset($tmp) && is_resource($tmp)) {
            fclose($tmp);
        }
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
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Language cache not found']);
        exit;
    }
    header('Content-Type: application/json');
    readfile($cacheFile);
    exit;
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

<?php
use League\CommonMark\GithubFlavoredMarkdownConverter;
use OpenBKAdmin\Device;
use OpenBKAdmin\Helper\FirmwareFolderHelper;
use OpenBKAdmin\Helper\GuzzleFactory;
use OpenBKAdmin\Helper\OtaHelper;
use OpenBKAdmin\Helper\OpenBekenHelper;
use OpenBKAdmin\Helper\OpenBekenOtaScraper;
use OpenBKAdmin\OpenBeken;
use OpenBKAdmin\Update\FirmwareDownloader;

$OpenBeken = $container->get(OpenBeken::class);
$devices = $OpenBeken->getDevices();
$rawSelectedIds = $_POST['device_ids'] ?? $_POST['device'] ?? $_POST['devices'] ?? [];
if (empty($rawSelectedIds) && isset($_POST['device_id'])) $rawSelectedIds = (array) $_POST['device_id'];
$selectedIds = array_values(array_unique(array_map('intval', (array) $rawSelectedIds)));
$selected = array_values(array_filter($devices, static fn (Device $d) => in_array((int) $d->id, $selectedIds, true)));

$targets = json_decode((string) ($_POST['update_targets'] ?? '{}'), true) ?: [];
$isAutomatic = false;
foreach ($targets as $target) {
    if (($target['source'] ?? '') === 'automatic') { $isAutomatic = true; break; }
}

$deviceMeta = [];
$otaErrors = [];

$detectPlatform = static function ($status): string {
    $hardware = strtoupper((string) ($status->StatusFWR->Hardware ?? ''));
    $version = strtoupper((string) ($status->StatusFWR->Version ?? ''));
    if (preg_match('/(?:OPEN)?(BK\d+[A-Z]*|XR\d+|BL\d+|W\d+|LN\d+[A-Z]*|TR\d+|RTL\d+[A-Z0-9]*|ESP32[A-Z0-9_-]*)/', $hardware.' '.$version, $m)) {
        return preg_replace('/^OPEN/', '', $m[1]);
    }
    return 'UNKNOWN';
};

// For an official release, never trust a chipset selected in the browser.
// Read Status 0 from every selected physical device and build one OTA target per chipset.
if ($isAutomatic && !empty($selected)) {
    $requiredPlatforms = [];
    $statusByAddress = [];
    foreach ($selected as $device) {
        $address = $device->getAddress();
        if (!isset($statusByAddress[$address])) $statusByAddress[$address] = $OpenBeken->getOpenBekenStatus($device);
        $status = $statusByAddress[$address];
        $platform = $detectPlatform($status);
        $fullName = trim((string) ($status->OpenBeken->FriendlyName ?? ''));
        if ($fullName === '') $fullName = trim((string) ($status->Status->FriendlyName[0] ?? $device->getName()));
        $deviceMeta[(int) $device->id] = ['platform' => $platform, 'name' => $fullName];
        if ($platform === 'UNKNOWN') {
            $otaErrors[] = 'Could not detect chipset for '.$fullName.' ('.$device->ip.').';
        } else {
            $requiredPlatforms[$platform] = true;
        }
    }

    if (empty($otaErrors)) {
        try {
            $firmwarefolder = _DATADIR_.'firmwares/';
            FirmwareFolderHelper::clean($firmwarefolder);
            $client = GuzzleFactory::getClient($Config);
            $helper = new OpenBekenHelper(new GithubFlavoredMarkdownConverter(), $client, new OpenBekenOtaScraper($Config->read('auto_update_channel'), $client), $Config->read('auto_update_channel'));
            $downloader = new FirmwareDownloader($client, $firmwarefolder);
            $otaHelper = new OtaHelper($Config, _BASEURL_);
            $targets = [];
            foreach (array_keys($requiredPlatforms) as $platform) {
                $release = $helper->getLatestFirmwares($platform);
                $path = $downloader->download($release->getFirmwareUrl());
                $targets[$platform] = [
                    'minimalOtaUrl' => '',
                    'otaUrl' => $otaHelper->getFirmwareUrl($path),
                    'targetVersion' => $release->getTagName(),
                    'source' => 'automatic',
                    'platform' => $platform,
                ];
            }
        } catch (Throwable $e) {
            $otaErrors[] = $e->getMessage();
        }
    }
}

$deviceDisplayName = static function (Device $device) use ($deviceMeta): string {
    if (!empty($deviceMeta[(int) $device->id]['name'])) return $deviceMeta[(int) $device->id]['name'];
    $friendlyNames = array_values(array_filter($device->getFriendlyNames(), static fn ($name) => '' !== trim((string) $name)));
    return !empty($friendlyNames) ? (string) $friendlyNames[0] : $device->getName();
};
?>
<div class="container mt-4 update-page">
    <h2 class="text-center mb-4"><?php echo $title; ?></h2>
    <?php if (empty($selected)) { ?>
        <div class="alert alert-warning">No devices were selected for the firmware update.</div>
    <?php } elseif (!empty($otaErrors)) { ?>
        <div class="alert alert-danger"><strong>Firmware update stopped for safety.</strong><br><?php echo htmlspecialchars(implode(' ', $otaErrors), ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } else { ?>
        <?php if ($isAutomatic) { ?>
        <div class="card mb-4"><div class="card-body"><h5 class="card-title">Selected devices / detected chipsets</h5><ul class="mb-0">
            <?php foreach ($selected as $d) { $meta = $deviceMeta[(int) $d->id] ?? []; ?>
                <li><?php echo htmlspecialchars($deviceDisplayName($d), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars((string) ($meta['platform'] ?? 'UNKNOWN'), ENT_QUOTES, 'UTF-8'); ?> — <?php echo htmlspecialchars($d->ip, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php } ?>
        </ul></div></div>
        <?php } ?>
        <input type="hidden" id="update_targets" value="<?php echo htmlspecialchars(json_encode($targets), ENT_QUOTES, 'UTF-8'); ?>">
        <div id="logGlobal" class="mb-3"></div>
        <div id="progressbox"></div>
        <script>const devices = <?php echo json_encode(array_map(static fn (Device $d) => ['id'=>$d->id,'name'=>$deviceDisplayName($d)], $selected)); ?>;</script>
        <script src="<?php echo $urlHelper->js('compiled/device_update'); ?>"></script>
    <?php } ?>
</div>

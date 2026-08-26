<?php

use OpenBKAdmin\Device;
use OpenBKAdmin\Helper\FirmwareFolderHelper;
use OpenBKAdmin\Helper\FirmwareVersionExtractor;
use OpenBKAdmin\Helper\OtaHelper;
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);
$errors = [];
$messages = [];
$updateTargets = [];
$firmwarefolder = _DATADIR_.'firmwares/';
FirmwareFolderHelper::clean($firmwarefolder);

$ota_server_ip = trim((string) ($_REQUEST['ota_server_ip'] ?? ''));
$ota_server_port = trim((string) ($_REQUEST['ota_server_port'] ?? ''));
if ($ota_server_ip !== '') $Config->write('ota_server_ip', $ota_server_ip);
if ($ota_server_port !== '') $Config->write('ota_server_port', $ota_server_port);
$otaHelper = new OtaHelper($Config, _BASEURL_);

if (isset($_REQUEST['auto'])) {
    // Do not call GitHub here. The chipset(s) are not known until the user
    // chooses devices. device_update.php resolves every required chipset from
    // one cached OpenBeken release request after selection.
    $updateTargets['automatic'] = [
        'minimalOtaUrl' => '',
        'otaUrl' => '',
        'targetVersion' => '',
        'source' => 'automatic',
        'platform' => 'AUTO',
    ];
    $messages[] = 'Official OpenBeken release selected. Choose the devices below; chipset and firmware will be resolved automatically.';
} elseif (isset($_REQUEST['upload'])) {
    try {
        if (!isset($_FILES['new_firmware']) || !is_array($_FILES['new_firmware'])) {
            throw new RuntimeException('No firmware file was received.');
        }
        if (UPLOAD_ERR_OK !== (int) ($_FILES['new_firmware']['error'] ?? UPLOAD_ERR_NO_FILE)) {
            throw new RuntimeException('Firmware upload failed (code '.(int) $_FILES['new_firmware']['error'].').');
        }
        if ((int) $_FILES['new_firmware']['size'] > 5 * 1024 * 1024) {
            throw new RuntimeException('Firmware file is larger than 5 MB.');
        }
        $originalName = basename((string) $_FILES['new_firmware']['name']);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['rbl', 'bin', 'img'], true)) {
            throw new RuntimeException('Unsupported firmware format. Use .rbl, .bin or .img.');
        }
        $safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName);
        $newFirmwarePath = $firmwarefolder.$safeName;
        if (!move_uploaded_file($_FILES['new_firmware']['tmp_name'], $newFirmwarePath)) {
            throw new RuntimeException('Could not save the uploaded firmware.');
        }
        $targetVersion = FirmwareVersionExtractor::fromFilename($originalName) ?? '';
        $updateTargets['default'] = [
            'minimalOtaUrl' => '',
            'otaUrl' => $otaHelper->getFirmwareUrl($newFirmwarePath),
            'targetVersion' => $targetVersion,
            'source' => 'manual',
            'platform' => 'LOCAL',
        ];
        $messages[] = 'Local firmware uploaded successfully: '.htmlspecialchars($originalName, ENT_QUOTES, 'UTF-8');
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
} else {
    $errors[] = __('UPLOAD_PLEASE_UPLOAD_FIRMWARE', 'DEVICE_UPDATE');
}

$devices = $OpenBeken->getDevices();
$disabledDeviceIds = array_map(static fn (Device $device) => $device->id, array_filter($devices, static fn (Device $device) => !$device->isUpdatable));
?>
<div class='row justify-content-sm-center update-page firmware-upload-page'>
<div class='col col-12 col-xl-10'>
<h2 class='text-sm-center mb-4'><?php echo $title; ?></h2>
<?php if (!empty($errors)) { ?>
<div class="alert alert-danger fade show mb-4" role="alert"><?php echo implode('<br/>', array_map(static fn ($e) => htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8'), $errors)); ?></div>
<?php } else { ?>
<?php if (!empty($messages)) { ?><div class="alert alert-success fade show mb-4" role="alert"><?php echo implode('<br/>', $messages); ?></div><?php } ?>
<?php if (isset($_REQUEST['auto'])) { ?><div class="alert alert-warning fade show mb-4" role="alert"><?php echo __('AUTO_WARNING_CFG_HOLDER', 'DEVICE_UPDATE'); ?></div><?php } ?>
<div class='card update-card update-selection-card mb-4'><div class='card-body'>
<div class='mb-4 text-center'><h3 class='mb-0'><?php echo __('CHOOSE_DEVICES_TO_UPDATE', 'DEVICE_UPDATE'); ?></h3></div>
<form name='update_devices' class='update-device-form' id='update_devices' method='post' action='<?php echo _BASEURL_; ?>device_update'>
<input type='hidden' name='update_targets' value='<?php echo htmlspecialchars(json_encode($updateTargets), ENT_QUOTES, 'UTF-8'); ?>'>
<div class='row g-3 update-toolbar mb-4'>
<div class='col col-12 col-md-auto'><button type='submit' class='btn btn-success w-100' name='submit' value='submit'><?php echo __('BTN_START_UPDATE', 'DEVICE_UPDATE'); ?></button></div>
<div class='col col-12 col-md-auto'><div class="form-check ps-0 mb-0"><input type="checkbox" class="form-check-input showmore d-none" id="showmore_top" name='showmore'><label class="form-check-label btn btn-secondary w-100" for="showmore_top"><?php echo __('SHOW_MORE', 'DEVICES'); ?></label></div></div>
<?php if (1 == $Config->read('show_search')) { ?><div class="col col-12 col-lg"><div class="input-group device-search-group"><input type="text" name="searchterm" class='form-control device-search has-clearer' placeholder="<?php echo __('FILTER', 'DEVICES'); ?>"><div class="input-group-text"><span class="input-group-text"><i class="fas fa-search"></i></span></div></div></div><?php } ?>
</div>
<div class='row justify-content-center'><div class='col'><div class='table-responsive double-scroll update-table-wrap'>
<?php $deviceLinks = true; $deviceLinkActionText = __('UPDATE', 'DEVICE_UPDATE'); include 'elements/devices_table.php'; ?>
</div></div></div>
<div class='row g-3 update-actions-row mt-2'><div class='col col-12 col-md-auto'><button type='submit' class='btn btn-success w-100' name='submit' value='submit'><?php echo __('BTN_START_UPDATE', 'DEVICE_UPDATE'); ?></button></div></div>
</form>
</div></div>
<?php } ?>
</div></div>
<script src="<?php echo $urlHelper->js('compiled/devices'); ?>"></script>

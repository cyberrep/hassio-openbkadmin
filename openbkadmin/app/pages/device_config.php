<?php
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);
$device = $OpenBeken->getDeviceById($device_id);
if (!$device) {
    echo '<div class="alert alert-danger">'.__('DEVICE_NOT_FOUND', 'DEVICE_CONFIG').'</div>';
    return;
}
$status = $OpenBeken->getOpenBekenStatus($device);
$info = ($status && isset($status->OpenBeken)) ? $status->OpenBeken : (object) [
    'ShortName' => '', 'FriendlyName' => '', 'ChannelLabels' => []
];
$rawFirmwareVersion = (string) ($status->StatusFWR->Version ?? '');
$firmwareVersion = $rawFirmwareVersion ?: '-';
$firmwareChipset = (string) ($status->StatusFWR->Hardware ?? $status->StatusFWR->Core ?? '');
if (preg_match('/^Open([^_]+)_(.+)$/i', $rawFirmwareVersion, $m)) {
    $firmwareChipset = $m[1];
    $firmwareVersion = $m[2];
}
if ('' === trim($firmwareChipset)) {
    $firmwareChipset = '-';
}
?>
<div class="row justify-content-sm-center">
  <div class="col col-12 col-lg-8">
    <h2 class="text-center mb-4">
      <?php echo __('DEVICE', 'DEVICE_CONFIG'); ?> <?php echo (int)$device->id; ?>
      <?php if ('' !== trim((string)$info->FriendlyName)) echo ' - '.htmlspecialchars($info->FriendlyName, ENT_QUOTES, 'UTF-8'); ?>
    </h2>

    <?php if ($status && isset($status->ERROR)) { ?>
      <div class="alert alert-danger"><?php echo htmlspecialchars($status->ERROR, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php } else { ?>
      <div class="card mb-4"><div class="card-body">
        <h4>OpenBeken</h4>
        <div class="row g-3">
          <div class="col-md-6"><strong>IP:</strong> <?php echo htmlspecialchars($device->ip); ?></div>
          <div class="col-md-6"><strong><?php echo __('POSITION', 'DEVICE_CONFIG'); ?>:</strong> <?php echo (int)$device->position; ?></div>
          <div class="col-md-6"><strong><?php echo __('SHORT_NAME', 'DEVICE_CONFIG'); ?>:</strong> <?php echo htmlspecialchars($info->ShortName ?: '-'); ?></div>
          <div class="col-md-6"><strong><?php echo __('FULL_NAME', 'DEVICE_CONFIG'); ?>:</strong> <?php echo htmlspecialchars($info->FriendlyName ?: '-'); ?></div>
          <div class="col-md-6"><strong><?php echo __('VERSION', 'DEVICE_CONFIG'); ?>:</strong>
            <?php echo htmlspecialchars($firmwareVersion); ?></div>
          <div class="col-md-6"><strong><?php echo __('CHIPSET', 'DEVICE_CONFIG'); ?>:</strong>
            <?php echo htmlspecialchars($firmwareChipset); ?></div>
        </div>
      </div></div>

      <div class="card mb-4"><div class="card-body">
        <h4><?php echo __('CHANNELS', 'DEVICE_CONFIG'); ?></h4>
        <?php if (!empty($info->ChannelLabels)) { ?>
          <div class="list-group">
          <?php foreach ($info->ChannelLabels as $channel => $label) { ?>
            <div class="list-group-item d-flex justify-content-between">
              <span><?php echo __('CHANNEL', 'DEVICE_CONFIG'); ?> <?php echo (int)$channel; ?></span>
              <strong><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></strong>
            </div>
          <?php } ?>
          </div>
        <?php } else { ?>
          <p class="text-muted mb-0"><?php echo __('NO_CHANNEL_LABELS', 'DEVICE_CONFIG'); ?></p>
        <?php } ?>
      </div></div>

      <div class="d-grid gap-2 d-md-flex">
        <a class="btn btn-primary" href="<?php echo _BASEURL_; ?>device_action/edit/<?php echo $device->id; ?>">
          Editar dispositivo
        </a>
        <a class="btn btn-secondary" target="_blank" href="http://<?php echo htmlspecialchars($device->ip); ?>/">
          OpenBeken Web UI
        </a>
      </div>
    <?php } ?>
  </div>
</div>

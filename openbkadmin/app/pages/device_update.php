<?php
use OpenBKAdmin\Device;
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);
$devices = $OpenBeken->getDevices();
$selectedIds = array_map('intval', $_POST['device'] ?? $_POST['devices'] ?? []);
if (empty($selectedIds) && isset($_POST['device_id'])) {
    $selectedIds = array_map('intval', (array) $_POST['device_id']);
}
$selected = array_values(array_filter($devices, static fn (Device $d) => in_array($d->id, $selectedIds, true)));
$targets = $_POST['update_targets'] ?? '{}';
?>
<div class="container mt-4 update-page">
    <h2 class="text-center mb-4"><?php echo $title; ?></h2>
    <?php if (empty($selected)) { ?>
        <div class="alert alert-warning">No devices were selected for the firmware update.</div>
    <?php } else { ?>
        <input type="hidden" id="update_targets" value="<?php echo htmlspecialchars($targets, ENT_QUOTES, 'UTF-8'); ?>">
        <div id="logGlobal" class="mb-3"></div>
        <div id="progressbox"></div>
        <script>const devices = <?php echo json_encode(array_map(static fn (Device $d) => ['id'=>$d->id,'name'=>$d->getName()], $selected)); ?>;</script>
        <script src="<?php echo $urlHelper->js('compiled/device_update'); ?>"></script>
    <?php } ?>
</div>

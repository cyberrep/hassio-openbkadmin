<?php

use OpenBKAdmin\Device;
use OpenBKAdmin\DeviceFactory;
use OpenBKAdmin\DeviceRepository;
use OpenBKAdmin\OpenBeken;

function normalizeDeviceNames($values): array
{
    if (!is_array($values)) return [];
    return array_values(array_filter(array_map(static fn ($value): string => trim((string) $value), $values), static fn (string $value): bool => '' !== $value));
}

function hasReachableStatus($status): bool
{
    return $status instanceof stdClass && !empty((array) $status) && !isset($status->ERROR);
}

function hasStatusError($status): bool
{
    return $status instanceof stdClass && isset($status->ERROR) && '' !== $status->ERROR;
}

function getFriendlyNamesFromStatus($status): array
{
    if (!hasReachableStatus($status) || !isset($status->Status)) return [];
    $source = $status->Status->FriendlyName ?? [];
    if (!is_array($source)) $source = [$source];
    return normalizeDeviceNames($source);
}

function quoteObkArgument(string $value): string
{
    return '"'.str_replace(['\\', '"'], ['\\\\', '\\"'], trim($value)).'"';
}

$status = null;
$device = null;
$msg = null;
$OpenBeken = $container->get(OpenBeken::class);
$deviceRepository = $container->get(DeviceRepository::class);

if ('edit' == $action) {
    $device = $deviceRepository->getDeviceById((int) $device_id);
    if ($device instanceof Device) {
        $status = $OpenBeken->getOpenBekenStatus($device);
        if (hasStatusError($status)) {
            $msg = __('MSG_DEVICE_NOT_FOUND', 'DEVICE_ACTIONS').'<br/>'.$status->ERROR.'<br/>';
        }
    }
} elseif ('delete' == $action) {
    $deviceRepository->removeDevice((int) $device_id);
    $msg = __('MSG_DEVICE_DELETE_DONE', 'DEVICE_ACTIONS');
    $action = 'done';
}

if (!empty($_POST)) {
    if (isset($_REQUEST['search'])) {
        $deviceIp = trim((string) ($_REQUEST['device_ip'] ?? ''));
        if ('' === $deviceIp) {
            $msg = __('ERROR_PLEASE_ENTER_DEVICE_IP', 'DEVICE_ACTIONS');
        } else {
            if (!($device instanceof Device) && !empty($_REQUEST['device_id'])) $device = $deviceRepository->getDeviceById((int) $_REQUEST['device_id']);
            if (!$device instanceof Device) {
                $device = DeviceFactory::fakeDevice($deviceIp, (int) ($_REQUEST['device_port'] ?? Device::DEFAULT_PORT), (string) ($_REQUEST['device_username'] ?? ''), (string) ($_REQUEST['device_password'] ?? ''));
            }
            $device->ip = $deviceIp;
            $device->port = (int) ($_REQUEST['device_port'] ?? Device::DEFAULT_PORT);
            $device->username = (string) ($_REQUEST['device_username'] ?? '');
            $device->password = (string) ($_REQUEST['device_password'] ?? '');
            if (!$OpenBeken->isOpenBeken($device)) {
                $status = new \stdClass();
                $status->ERROR = __('ERROR_NOT_OPENBEKEN', 'DEVICE_ACTIONS');
                $msg = __('MSG_DEVICE_NOT_FOUND', 'DEVICE_ACTIONS').'<br/>'.$status->ERROR.'<br/>';
            } else {
                $status = $OpenBeken->getOpenBekenStatus($device);
                if (hasStatusError($status)) $msg = __('MSG_DEVICE_NOT_FOUND', 'DEVICE_ACTIONS').'<br/>'.$status->ERROR.'<br/>';
            }
        }
    } elseif (!empty($_REQUEST['device_id'])) {
        $existingDevice = $deviceRepository->getDeviceById((int) $_REQUEST['device_id']);
        $postedFullName = trim((string) ($_REQUEST['device_full_name'] ?? ''));
        $postedShortName = trim((string) ($_REQUEST['device_short_name'] ?? ''));
        $postedChannelLabels = normalizeDeviceNames($_REQUEST['device_channel_label'] ?? []);
        $localNames = normalizeDeviceNames($_REQUEST['device_name'] ?? []);
        if ([] === $localNames) $localNames = $postedChannelLabels;
        if ([] === $localNames && '' !== $postedFullName) $localNames = [$postedFullName];
        $_REQUEST['device_name'] = $localNames;
        $_REQUEST['device_friendly_name'] = $postedChannelLabels ?: $localNames;
        $device = DeviceFactory::fromRequest($_REQUEST);

        $remoteErrors = [];
        if ($existingDevice instanceof Device) {
            $existingDevice->ip = $device->ip;
            $existingDevice->port = $device->port;
            $existingDevice->username = $device->username;
            $existingDevice->password = $device->password;

            if ('' !== $postedFullName) {
                $remoteResult = $OpenBeken->doAjax((int) $existingDevice->id, 'FriendlyName '.quoteObkArgument($postedFullName));
                if (isset($remoteResult->ERROR)) $remoteErrors[] = 'Full Name: '.$remoteResult->ERROR;
            }
            if ('' !== $postedShortName) {
                $remoteResult = $OpenBeken->doAjax((int) $existingDevice->id, 'ShortName '.quoteObkArgument($postedShortName));
                if (isset($remoteResult->ERROR)) $remoteErrors[] = 'Short Name: '.$remoteResult->ERROR;
            }
            foreach ($postedChannelLabels as $index => $label) {
                $remoteResult = $OpenBeken->doAjax((int) $existingDevice->id, 'SetChannelLabel '.$index.' '.quoteObkArgument($label).' 1');
                if (isset($remoteResult->ERROR)) $remoteErrors[] = 'Channel '.($index + 1).': '.$remoteResult->ERROR;
            }
        }

        $deviceRepository->updateDevice($device);
        $msg = __('MSG_DEVICE_EDIT_DONE', 'DEVICE_ACTIONS');
        if ([] !== $remoteErrors) $msg .= '<br><small>OpenBeken: '.htmlspecialchars(implode(' | ', $remoteErrors), ENT_QUOTES, 'UTF-8').'</small>';
        $action = 'done';
    } else {
        $deviceRepository->addDevice($_REQUEST);
        $msg = __('MSG_DEVICE_ADD_DONE', 'DEVICE_ACTIONS');
        $action = 'done';
    }
}

$showDeviceFields = ('edit' == $action && $device instanceof Device) || hasReachableStatus($status);
$canSave = ('edit' == $action && $device instanceof Device) || hasReachableStatus($status);
$openBekenFullName = trim((string) ($status->OpenBeken->FriendlyName ?? ''));
$openBekenShortName = trim((string) ($status->OpenBeken->ShortName ?? ''));
if ('' === $openBekenFullName && $device instanceof Device) {
    $storedNames = normalizeDeviceNames($device->names);
    if ([] !== $storedNames) {
        $candidate = $storedNames[0];
        if (count($storedNames) > 1 && false !== strrpos($candidate, ' - ')) $candidate = substr($candidate, 0, strrpos($candidate, ' - '));
        $openBekenFullName = trim($candidate);
    }
}
if ('' === $openBekenShortName && $device instanceof Device && '' !== trim((string) $device->mqttTopic)) {
    $topicParts = explode('/', trim((string) $device->mqttTopic, '/'));
    $openBekenShortName = trim((string) end($topicParts));
}
$channelLabels = getFriendlyNamesFromStatus($status);
if ([] === $channelLabels && $device instanceof Device) $channelLabels = normalizeDeviceNames($device->getFriendlyNames());
if ([] === $channelLabels && $device instanceof Device) $channelLabels = normalizeDeviceNames($device->names);
$channelLabels = array_values(array_map(static function (string $label) use ($openBekenFullName): string {
    $label = trim($label);
    if ('' !== $openBekenFullName && 0 === stripos($label, $openBekenFullName.' - ')) return trim(substr($label, strlen($openBekenFullName.' - ')));
    return $label;
}, $channelLabels));
if ([] === $channelLabels) $channelLabels = [''];
$deviceConfirmToggle = array_key_exists('device_confirm_toggle', $_REQUEST)
    ? '1' === (string) $_REQUEST['device_confirm_toggle']
    : (($device instanceof Device) ? $device->deviceConfirmToggle : ('1' === $Config->read('confirm_device_toggles')));
?>
<div class='row justify-content-sm-center'>
	<div class='col col-12 col-md-8 col-xl-6'>
		<h2 class='text-sm-center mb-5'><?php echo $title; ?></h2>
		<?php if (hasStatusError($status)) { ?>
			<div class="alert alert-danger alert-dismissible fade show mb-5" role="alert"><p><?php echo __('MSG_DEVICE_NOT_FOUND', 'DEVICE_ACTIONS'); ?></p><p><?php echo $status->ERROR; ?></p><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
		<?php } elseif (null !== $msg && '' !== $msg && 'done' !== $action) { ?>
			<div class="alert alert-danger alert-dismissible fade show mb-5" role="alert"><p><?php echo $msg; ?></p><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
		<?php } elseif ('done' !== $action && hasReachableStatus($status)) { ?>
			<div class="alert alert-success alert-dismissible fade show my-5" role="alert"><?php echo __('MSG_DEVICE_FOUND', 'DEVICE_ACTIONS'); ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
		<?php } ?>
		<?php if ('done' == $action) { ?>
			<div class="alert alert-success fade show mb-5" role="alert"><div class="col col-12 text-start"><?php echo $msg; ?></div><div class="col col-12 text-start mt-3"><a class="btn btn-secondary col-12 col-sm-auto" href='<?php echo _BASEURL_; ?>devices'><?php echo __('BTN_BACK', 'DEVICE_ACTIONS'); ?></a></div></div>
		<?php } ?>
		<?php if ('add' == $action || 'edit' == $action) { ?>
			<?php if (isset($device->id)) { ?>
				<h3 class='text-sm-center mb-5'><?php echo __('DEVICE', 'DEVICE_CONFIG'); ?> <?php echo $device->id; ?><?php echo '' !== $openBekenFullName ? ' - '.htmlspecialchars($openBekenFullName, ENT_QUOTES, 'UTF-8') : ''; ?></h3>
			<?php } ?>
			<form class='form' name='save_device' method='post' action='<?php echo _BASEURL_; ?>device_action/<?php echo $action; ?><?php echo isset($device->id) ? '/'.$device->id : ''; ?>'>
				<input type='hidden' name='device_id' value='<?php echo $device->id ?? ''; ?>'>
				<div class="row g-3">
					<div class="form-group col col-12 col-sm-6"><label for="device_ip"><?php echo __('DEVICE_IP', 'DEVICE_ACTIONS'); ?></label><input type="text" autofocus="autofocus" class="form-control" id="device_ip" name='device_ip' value='<?php echo htmlspecialchars((string) (isset($device->id) && !isset($_REQUEST['device_ip']) ? $device->ip : ($_REQUEST['device_ip'] ?? '')), ENT_QUOTES); ?>' required><small class="text-muted"><?php echo __('DEVICE_IP_HELP', 'DEVICE_ACTIONS'); ?></small></div>
					<div class="form-group col col-12 col-sm-3"><label for="device_port"><?php echo __('DEVICE_PORT', 'DEVICE_ACTIONS'); ?></label><input type="text" class="form-control" id="device_port" name='device_port' value='<?php echo htmlspecialchars((string) (isset($device->port) && !isset($_REQUEST['device_port']) ? $device->port : ($_REQUEST['device_port'] ?? Device::DEFAULT_PORT)), ENT_QUOTES); ?>' required><small class="text-muted"><?php echo __('DEVICE_PORT_HELP', 'DEVICE_ACTIONS'); ?></small></div>
					<div class="form-group col col-12 col-sm-3"><label class="d-none d-sm-block">&nbsp;</label><button type='submit' name='search' value='search' class='btn btn-primary col-12'><?php echo __('BTN_SEARCH_DEVICE', 'DEVICE_ACTIONS'); ?></button></div>
				</div>
				<div class="form-group col"><label for="device_username"><?php echo __('DEVICE_USERNAME', 'DEVICE_ACTIONS'); ?></label><input type="text" autocomplete='off' class="form-control" id="device_username" name='device_username' value='<?php echo htmlspecialchars((string) (isset($device->id) && !isset($_REQUEST['device_username']) ? $device->username : ($_REQUEST['device_username'] ?? 'admin')), ENT_QUOTES); ?>'><small class="text-muted"><?php echo __('DEVICE_USERNAME_HELP', 'DEVICE_ACTIONS'); ?></small></div>
				<div class="form-group col"><label for="device_password"><?php echo __('DEVICE_PASSWORD', 'DEVICE_ACTIONS'); ?></label><input type="password" autocomplete='off' class="form-control" id="device_password" name='device_password' value='<?php echo htmlspecialchars((string) (isset($device->id) && !isset($_REQUEST['device_password']) ? $device->password : ($_REQUEST['device_password'] ?? '')), ENT_QUOTES); ?>'><small class="text-muted"><?php echo __('DEVICE_PASSWORD_HELP', 'DEVICE_ACTIONS'); ?></small></div>
				<div class="form-group col"><label for="device_mqtt_topic"><?php echo __('TOPIC', 'DEVICE_CONFIG'); ?></label><input type="text" class="form-control" id="device_mqtt_topic" name='device_mqtt_topic' value='<?php echo htmlspecialchars((string) (isset($device->mqttTopic) && !isset($_REQUEST['device_mqtt_topic']) ? $device->mqttTopic : ($_REQUEST['device_mqtt_topic'] ?? '')), ENT_QUOTES); ?>'><small class="text-muted"><?php echo __('MQTT_DEVICE_TOPIC_HELP', 'DEVICE_ACTIONS'); ?></small></div>

				<?php if ($showDeviceFields) { ?>
					<div class="card mb-4"><div class="card-body">
						<h5 class="card-title mb-3">OpenBeken</h5>
						<div class="form-group"><label for="device_full_name">Full Name</label><input type="text" class="form-control" id="device_full_name" name="device_full_name" value="<?php echo htmlspecialchars($openBekenFullName, ENT_QUOTES, 'UTF-8'); ?>" required><small class="form-text text-muted">OpenBeken Full Name</small></div>
						<div class="form-group"><label for="device_short_name">Short Name</label><input type="text" class="form-control" id="device_short_name" name="device_short_name" value="<?php echo htmlspecialchars($openBekenShortName, ENT_QUOTES, 'UTF-8'); ?>"><small class="form-text text-muted">OpenBeken ShortName / MQTT device name</small></div>
					</div></div>
					<div class="form-group col"><label for="device_position"><?php echo __('DEVICE_POSITION', 'DEVICE_ACTIONS'); ?></label><input type="text" class="form-control" id="device_position" name='device_position' value='<?php echo htmlspecialchars((string) (isset($device->position) && !isset($_REQUEST['device_position']) ? $device->position : ($_REQUEST['device_position'] ?? '')), ENT_QUOTES); ?>'><small class="form-text text-muted"><?php echo __('DEVICE_POSITION_HELP', 'DEVICE_ACTIONS'); ?></small></div>

					<div class="card mb-4"><div class="card-body"><h5 class="card-title mb-3">Channels</h5>
					<?php foreach ($channelLabels as $index => $label) { $channelNumber = $index + 1; $displayName = '' !== $openBekenFullName && '' !== $label ? $openBekenFullName.' - '.$label : ($label ?: $openBekenFullName); ?>
						<div class="row g-3 device-name-row mb-2">
							<div class="form-group col col-12 col-sm-4"><label>Channel <?php echo $channelNumber; ?></label><input type="text" class="form-control" value="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>" readonly></div>
							<div class="form-group col col-12 col-sm-8"><label for="device_channel_label_<?php echo $index; ?>">Channel Name</label><input type="text" class="form-control" id="device_channel_label_<?php echo $index; ?>" name="device_channel_label[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"><input type="hidden" name="device_name[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>"></div>
						</div>
					<?php } ?>
					</div></div>

					<div class="row g-3">
						<?php $checks = [
							['device_all_off', 'deviceAllOff', 'LABEL_ALL_OFF'],
							['device_protect_on', 'deviceProtectionOn', 'LABEL_PROTECT_ON'],
							['device_protect_off', 'deviceProtectionOff', 'LABEL_PROTECT_OFF'],
							['device_confirm_toggle', 'deviceConfirmToggle', 'LABEL_CONFIRM_DEVICE_TOGGLE'],
							['is_updatable', 'isUpdatable', 'LABEL_IS_UPDATABLE'],
							['device_hide_from_startpage', 'deviceHideFromStartpage', 'LABEL_HIDE_FROM_STARTPAGE'],
						]; foreach ($checks as [$field, $property, $labelKey]) { $checked = $device instanceof Device && $device->{$property}; ?>
						<div class="form-group col col-12 col-sm-6 col-lg-4"><div class="form-check mb-4"><input type="hidden" name="<?php echo $field; ?>" value="0"><input class="form-check-input" type="checkbox" value="1" id="<?php echo $field; ?>" name="<?php echo $field; ?>" <?php echo $checked ? 'checked="checked"' : ''; ?>><label class="form-check-label" for="<?php echo $field; ?>"><?php echo __($labelKey, 'DEVICE_ACTIONS'); ?></label></div></div>
						<?php } ?>
					</div>
				<?php } ?>

				<div class="row"><div class="col col-12 col-sm-6 text-start"><a class="btn btn-secondary col-12 col-sm-auto" href='<?php echo _BASEURL_; ?>devices'><?php echo __('BTN_BACK', 'DEVICE_ACTIONS'); ?></a></div><div class="col col-12 col-sm-6 text-end"><button type='submit' name='submit' value='<?php echo isset($device->id) ? 'edit' : 'add'; ?>' class='btn btn-primary col-12 col-sm-auto' <?php echo !$canSave ? 'disabled' : ''; ?>><?php echo __('BTN_SAVE', 'DEVICE_ACTIONS'); ?></button></div></div>
			</form>
		<?php } ?>
	</div>
</div>

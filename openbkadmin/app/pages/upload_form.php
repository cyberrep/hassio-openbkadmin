<?php
use League\CommonMark\GithubFlavoredMarkdownConverter;
use OpenBKAdmin\Helper\GuzzleFactory;
use OpenBKAdmin\Helper\OpenBekenHelper;
use OpenBKAdmin\Helper\OpenBekenOtaScraper;

$client = GuzzleFactory::getClient($Config);
$OpenBekenHelper = new OpenBekenHelper(new GithubFlavoredMarkdownConverter(), $client, new OpenBekenOtaScraper($Config->read('auto_update_channel'), $client), $Config->read('auto_update_channel'));
$releaseNotes = $OpenBekenHelper->getReleaseNotes();
$changelog = $OpenBekenHelper->getChangelog();

$hostHeader = trim((string) ($_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? ''));
$detectedHost = preg_replace('/:\d+$/', '', $hostHeader);
if (str_starts_with($detectedHost, '[') && str_ends_with($detectedHost, ']')) $detectedHost = trim($detectedHost, '[]');
$configuredOtaIp = trim((string) $Config->read('ota_server_ip'));
if ($configuredOtaIp === '' || str_starts_with($configuredOtaIp, '172.30.')) $configuredOtaIp = $detectedHost;
$configuredOtaPort = trim((string) $Config->read('ota_server_port')) ?: (string) ($_SERVER['SERVER_PORT'] ?? '9542');
?>
<div class='row justify-content-sm-center upload-form-page'><div class='col col-12 col-md-9 col-xl-8'>
<h2 class='text-sm-center mb-3'><?php echo $title; ?></h2>
<div class="card upload-intro-card mb-4"><div class="card-body text-center"><p class="mb-2"><?php echo __('UPLOAD_DESCRIPTION', 'DEVICE_UPDATE'); ?></p><a href='https://github.com/openshwprojects/OpenBK7231T_App/releases' target='_blank' rel='noopener'>OpenBeken Releases</a></div></div>
<form class='upload-form' method='post' enctype='multipart/form-data' action='<?php echo _BASEURL_; ?>upload'>
<div class="card upload-form-card mb-4"><div class="card-body">
<h4 class="mb-3">OTA Server</h4>
<div class='row g-4 mb-4'><div class="col col-12 col-sm-9"><label for="ota_server_ip" class="form-label"><?php echo __('CONFIG_SERVER_IP', 'USER_CONFIG'); ?></label><input type="text" class="form-control" id="ota_server_ip" name='ota_server_ip' required value='<?php echo htmlspecialchars($configuredOtaIp, ENT_QUOTES, 'UTF-8'); ?>'><div class="form-text">Defaults to the Home Assistant/OpenBKAdmin host reachable by your OpenBeken devices.</div></div><div class="col col-12 col-sm-3"><label for="ota_server_port" class="form-label"><?php echo __('CONFIG_SERVER_PORT', 'USER_CONFIG'); ?></label><input type="text" class="form-control" id="ota_server_port" name='ota_server_port' required value='<?php echo htmlspecialchars($configuredOtaPort, ENT_QUOTES, 'UTF-8'); ?>'></div></div>
<hr><h4 class="mb-3">Official OpenBeken Release</h4>
<div class="row g-3 align-items-end mb-4"><div class="col"><label class="form-label">Chipset / OTA Update</label><input type="text" class="form-control" value="Check chipset automatically" readonly><input type="hidden" name="update_automatic_lang" value="BK7231N"><div class="form-text">The chipset is verified again for every selected physical device before the firmware update starts. Mixed chipsets are supported.</div></div><div class="col col-12 col-md-auto"><button type='submit' class='btn btn-primary w-100' id="automatic" name='auto' value='submit'>Use Official Release</button></div></div>
<hr><h4 class="mb-3">Local Firmware</h4>
<div class='row g-3 align-items-end'><div class="col"><label for="new_firmware" class="form-label">Firmware file</label><input type="file" class="form-control" id="new_firmware" name='new_firmware' accept=".rbl,.bin,.img"></div><div class="col col-12 col-md-auto"><button type='submit' class='btn btn-secondary w-100' id="localUpload" name='upload' value='submit'>Use Local Firmware</button></div></div>
</div></div></form>
<div class='row g-4 upload-notes-row'><div class='col col-12 col-md-6'><div class='card h-100'><div class='card-body changelog'><?php echo $releaseNotes; ?></div></div></div><div class='col col-12 col-md-6'><div class='card h-100'><div class='card-body changelog'><h4>OpenBeken Release Notes</h4><?php echo $changelog; ?></div></div></div></div>
</div></div>
<script>document.addEventListener('DOMContentLoaded',()=>{const f=document.querySelector('#new_firmware');const l=document.querySelector('#localUpload');if(l)l.addEventListener('click',e=>{if(!f.files.length){e.preventDefault();f.focus();f.reportValidity();}});});</script>

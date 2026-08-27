<?php
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);
$devices = $OpenBeken->getDevices();
$backupDir = _DATADIR_.'ota-backups/';
if (!is_dir($backupDir)) @mkdir($backupDir, 0775, true);

// Delete only files that are actually inside the OTA backup directory.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $name = basename((string) $_POST['delete_backup']);
    $path = $backupDir.$name;
    if ($name !== '' && is_file($path)) @unlink($path);
    header('Location: '._BASEURL_.'backups');
    exit;
}

// Download only a basename from the controlled backup directory.
if (isset($_GET['download'])) {
    $name = basename((string) $_GET['download']);
    $path = $backupDir.$name;
    if ($name === '' || !is_file($path)) {
        http_response_code(404);
        echo 'Backup not found';
        exit;
    }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.str_replace('"', '', $name).'"');
    header('Content-Length: '.filesize($path));
    header('X-Content-Type-Options: nosniff');
    readfile($path);
    exit;
}

$groups = [];
foreach ($devices as $device) {
    $id = (int) $device->id;
    if (!isset($groups[$id])) {
        $friendly = array_values(array_filter($device->getFriendlyNames(), static fn($v) => trim((string)$v) !== ''));
        $groups[$id] = [
            'name' => !empty($friendly) ? (string)$friendly[0] : $device->getName(),
            'ip' => (string)$device->ip,
            'files' => [],
        ];
    }
}

$unmatched = [];
foreach (glob($backupDir.'*.dmp') ?: [] as $path) {
    $file = basename($path);
    $entry = ['file' => $file, 'date' => filemtime($path) ?: 0, 'size' => filesize($path) ?: 0];
    $matched = false;
    // OpenBKAdmin backup names contain the device ID in the original dump basename.
    foreach (array_keys($groups) as $id) {
        if (preg_match('/(?:^|[-_])'.preg_quote((string)$id, '/').'(?:[-_.]|$)/', $file)) {
            $groups[$id]['files'][] = $entry;
            $matched = true;
            break;
        }
    }
    if (!$matched) $unmatched[] = $entry;
}
foreach ($groups as &$group) usort($group['files'], static fn($a,$b) => $b['date'] <=> $a['date']);
unset($group);
usort($unmatched, static fn($a,$b) => $b['date'] <=> $a['date']);

function obkBackupSize(int $bytes): string {
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.').' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 1, ',', '.').' KB';
    return $bytes.' B';
}
?>
<div class="container mt-4 backups-page">
  <h2 class="text-center mb-4">Backups</h2>
  <p class="text-muted">Backups de configuração criados automaticamente antes das atualizações OTA.</p>
  <?php foreach ($groups as $id => $group) { if (empty($group['files'])) continue; ?>
    <div class="card mb-4">
      <div class="card-header"><strong><?php echo htmlspecialchars($id.' - '.$group['name'], ENT_QUOTES, 'UTF-8'); ?></strong> <span class="text-muted">— <?php echo htmlspecialchars($group['ip'], ENT_QUOTES, 'UTF-8'); ?></span></div>
      <div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Backup</th><th>Data</th><th>Tamanho</th><th class="text-end">Ações</th></tr></thead><tbody>
      <?php foreach ($group['files'] as $backup) { ?>
        <tr><td><a href="<?php echo _BASEURL_; ?>backups?download=<?php echo rawurlencode($backup['file']); ?>" title="Download"><?php echo htmlspecialchars($backup['file'], ENT_QUOTES, 'UTF-8'); ?></a></td><td><?php echo date('d/m/Y H:i:s', $backup['date']); ?></td><td><?php echo obkBackupSize((int)$backup['size']); ?></td><td class="text-end"><form method="post" action="<?php echo _BASEURL_; ?>backups" class="d-inline" onsubmit="return confirm('Excluir este backup?');"><input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['file'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir" aria-label="Excluir"><i class="fas fa-times"></i></button></form></td></tr>
      <?php } ?>
      </tbody></table></div>
    </div>
  <?php } ?>
  <?php if (!empty($unmatched)) { ?>
    <div class="card mb-4"><div class="card-header"><strong>Outros backups</strong></div><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Backup</th><th>Data</th><th>Tamanho</th><th class="text-end">Ações</th></tr></thead><tbody>
    <?php foreach ($unmatched as $backup) { ?><tr><td><a href="<?php echo _BASEURL_; ?>backups?download=<?php echo rawurlencode($backup['file']); ?>"><?php echo htmlspecialchars($backup['file'], ENT_QUOTES, 'UTF-8'); ?></a></td><td><?php echo date('d/m/Y H:i:s', $backup['date']); ?></td><td><?php echo obkBackupSize((int)$backup['size']); ?></td><td class="text-end"><form method="post" action="<?php echo _BASEURL_; ?>backups" class="d-inline" onsubmit="return confirm('Excluir este backup?');"><input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['file'], ENT_QUOTES, 'UTF-8'); ?>"><button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir"><i class="fas fa-times"></i></button></form></td></tr><?php } ?>
    </tbody></table></div></div>
  <?php } ?>
  <?php $total = count($unmatched); foreach ($groups as $g) $total += count($g['files']); if ($total === 0) { ?><div class="alert alert-info">Nenhum backup OTA foi criado ainda.</div><?php } ?>
</div>

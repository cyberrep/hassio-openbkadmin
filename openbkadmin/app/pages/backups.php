<?php
use OpenBKAdmin\OpenBeken;

$OpenBeken = $container->get(OpenBeken::class);
$devices = $OpenBeken->getDevices();
$backupDir = _DATADIR_.'ota-backups/';
if (!is_dir($backupDir)) @mkdir($backupDir, 0775, true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_set'])) {
    $prefix = basename((string) $_POST['delete_set']);
    if ($prefix !== '' && preg_match('/^\d{8}-\d{6}-\d+-[A-Za-z0-9._-]+$/', $prefix)) {
        foreach ([$prefix.'.dmp', $prefix.'.fs.tar'] as $name) {
            $path = $backupDir.$name;
            if (is_file($path)) @unlink($path);
        }
    }
    header('Location: '._BASEURL_.'backups'); exit;
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_backup'])) {
    $name = basename((string) $_POST['delete_backup']); $path = $backupDir.$name;
    if ($name !== '' && is_file($path)) @unlink($path);
    header('Location: '._BASEURL_.'backups'); exit;
}
if (isset($_GET['download'])) {
    $name = basename((string) $_GET['download']); $path = $backupDir.$name;
    if ($name === '' || !is_file($path)) { http_response_code(404); echo 'Backup not found'; exit; }
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="'.str_replace('"', '', $name).'"');
    header('Content-Length: '.filesize($path)); header('X-Content-Type-Options: nosniff'); readfile($path); exit;
}

$groups=[];
foreach($devices as$device){$id=(int)$device->id;$friendly=array_values(array_filter($device->getFriendlyNames(),static fn($v)=>trim((string)$v)!==''));$groups[$id]=['name'=>!empty($friendly)?(string)$friendly[0]:$device->getName(),'ip'=>(string)$device->ip,'sets'=>[]];}
$unmatched=[];
foreach(glob($backupDir.'*')?:[] as$path){if(!is_file($path))continue;$file=basename($path);$type=null;$prefix=null;if(str_ends_with($file,'.fs.tar')){$type='fs';$prefix=substr($file,0,-7);}elseif(str_ends_with($file,'.dmp')){$type='dmp';$prefix=substr($file,0,-4);}else continue;$entry=['file'=>$file,'date'=>filemtime($path)?:0,'size'=>filesize($path)?:0];$matched=false;if(preg_match('/^(\d{8}-\d{6})-(\d+)-/',$prefix,$m)){$id=(int)$m[2];if(isset($groups[$id])){if(!isset($groups[$id]['sets'][$prefix]))$groups[$id]['sets'][$prefix]=['prefix'=>$prefix,'date'=>$entry['date'],'dmp'=>null,'fs'=>null];$groups[$id]['sets'][$prefix][$type]=$entry;$groups[$id]['sets'][$prefix]['date']=max($groups[$id]['sets'][$prefix]['date'],$entry['date']);$matched=true;}}if(!$matched)$unmatched[]=$entry;}
foreach($groups as&$group){$group['sets']=array_values($group['sets']);usort($group['sets'],static fn($a,$b)=>$b['date']<=>$a['date']);}unset($group);usort($unmatched,static fn($a,$b)=>$b['date']<=>$a['date']);
function obkBackupSize(int$bytes):string{if($bytes>=1048576)return number_format($bytes/1048576,2,',','.').' MB';if($bytes>=1024)return number_format($bytes/1024,1,',','.').' KB';return$bytes.' B';}
function obkBackupLink(?array$file,string$label):string{if(!$file)return'<span class="text-muted">—</span>';return'<a href="'._BASEURL_.'backups?download='.rawurlencode($file['file']).'" title="Download '.$label.'"><i class="fas fa-download"></i> '.htmlspecialchars($label,ENT_QUOTES,'UTF-8').'</a> <small class="text-muted">('.obkBackupSize((int)$file['size']).')</small>';}
?>
<div class="container mt-4 backups-page"><h2 class="text-center mb-4">Backups</h2><p class="text-muted">Cada backup OTA pode conter a configuração do OpenBeken e o filesystem LittleFS completo. Os 2 conjuntos mais recentes de cada dispositivo são mantidos automaticamente.</p>
<?php $totalSets=0;foreach($groups as$id=>$group){if(empty($group['sets']))continue;$totalSets+=count($group['sets']);?><div class="card mb-4"><div class="card-header"><strong><?php echo htmlspecialchars($id.' - '.$group['name'],ENT_QUOTES,'UTF-8');?></strong> <span class="text-muted">— <?php echo htmlspecialchars($group['ip'],ENT_QUOTES,'UTF-8');?></span></div><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Data</th><th>Configuração</th><th>Filesystem / autoexec</th><th class="text-end">Ações</th></tr></thead><tbody>
<?php foreach($group['sets'] as$set){?><tr><td><?php echo date('d/m/Y H:i:s',$set['date']);?></td><td><?php echo obkBackupLink($set['dmp'],'Config .dmp');?></td><td><?php echo obkBackupLink($set['fs'],'Filesystem .tar');?></td><td class="text-end"><form method="post" action="<?php echo _BASEURL_;?>backups" class="d-inline" onsubmit="return confirm('Excluir configuração e filesystem deste backup?');"><input type="hidden" name="delete_set" value="<?php echo htmlspecialchars($set['prefix'],ENT_QUOTES,'UTF-8');?>"><button type="submit" class="btn btn-sm btn-outline-danger" title="Excluir backup"><i class="fas fa-times"></i></button></form></td></tr><?php }?></tbody></table></div></div><?php }?>
<?php if(!empty($unmatched)){?><div class="card mb-4"><div class="card-header"><strong>Backups antigos / não associados</strong></div><div class="table-responsive"><table class="table table-hover mb-0 align-middle"><thead><tr><th>Arquivo</th><th>Data</th><th>Tamanho</th><th class="text-end">Ações</th></tr></thead><tbody><?php foreach($unmatched as$backup){?><tr><td><a href="<?php echo _BASEURL_;?>backups?download=<?php echo rawurlencode($backup['file']);?>"><?php echo htmlspecialchars($backup['file'],ENT_QUOTES,'UTF-8');?></a></td><td><?php echo date('d/m/Y H:i:s',$backup['date']);?></td><td><?php echo obkBackupSize((int)$backup['size']);?></td><td class="text-end"><form method="post" action="<?php echo _BASEURL_;?>backups" class="d-inline" onsubmit="return confirm('Excluir este backup?');"><input type="hidden" name="delete_backup" value="<?php echo htmlspecialchars($backup['file'],ENT_QUOTES,'UTF-8');?>"><button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-times"></i></button></form></td></tr><?php }?></tbody></table></div></div><?php }?>
<?php if($totalSets===0&&empty($unmatched)){?><div class="alert alert-info">Nenhum backup OTA foi criado ainda.</div><?php }?></div>

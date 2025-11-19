<?php
require 'db.php';
require 'functions.php';

$accId = (int)($_POST['acc_id'] ?? 0);
$lang  = $_POST['lang'] ?? '';
if(!$accId || !$lang) die('Parametri mancanti');

// recupera doc e codice accessorio
$doc = db_one($conn, "SELECT d.Id, d.FilePath, a.Code FROM dbo.AccessoryDoc d JOIN dbo.Accessory a ON a.Id=d.AccessoryId WHERE d.AccessoryId=? AND d.LangCode=?", [$accId, $lang]);
if(!$doc) die('Documento non trovato');

$codeUpper = strtoupper(preg_replace('/[^A-Za-z0-9_\-]/','_',$doc['Code'] ?? 'ACC'));
$langUpper = strtoupper($lang);

$archiveDir = __DIR__ . '/docs_archive/'.$codeUpper.'/'.$langUpper;
if (!is_dir($archiveDir)) mkdir($archiveDir, 0775, true);

$oldPath = (string)$doc['FilePath'];
$oldAbs = $oldPath;
if (!preg_match('~^[A-Za-z]:\\\\|^/|^\\\\~', $oldPath)) {
  $oldAbs = __DIR__ . '/' . ltrim($oldPath, '/\\');
}
$moved = false;
if (is_file($oldAbs)) {
  $base = basename($oldAbs);
  $dest = $archiveDir.'/'.$base;
  $suffix = 1;
  while (file_exists($dest)) {
    $dest = $archiveDir.'/'.pathinfo($base, PATHINFO_FILENAME).'_'.$suffix.'.pdf';
    $suffix++;
  }
  @rename($oldAbs, $dest);
  $moved = true;
}

// rimuove riga dal DB (segna come mancante)
db_exec($conn, "DELETE FROM dbo.AccessoryDoc WHERE Id=?", [(int)$doc['Id']]);

header('Location: docs_audit.php?deleted=1&move='.($moved?1:0));
exit;
?>

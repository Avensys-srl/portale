<?php
require 'db.php'; require 'functions.php';

$accId = (int)($_POST['acc_id'] ?? 0);
$lang  = $_POST['lang'] ?? '';
if(!$accId || !$lang || empty($_FILES['file']['tmp_name'])) die('Parametri mancanti');

$acc = db_one($conn, "SELECT Code FROM dbo.Accessory WHERE Id=?", [$accId]);
if(!$acc) die('Accessory non trovato');

$docsSetting = env_value('DOCS_DIR','docs');
$docsBase = env_path('DOCS_DIR','docs');
$docsIsAbsolute = preg_match('~^[A-Za-z]:[\\\\/]|^/|^\\\\~', $docsSetting);
$docsRel = $docsIsAbsolute ? rtrim($docsBase,'/\\') : trim($docsSetting,'/\\');
if($docsRel === '') $docsRel = 'docs';
$archiveBase = env_path('DOCS_ARCHIVE_DIR','docs_archive');

$code = preg_replace('/[^A-Za-z0-9_\-]/','_',$acc['Code']);
$codeUpper = strtoupper($code);
$langUpper = strtoupper($lang);

$targetDir = $docsBase . '/' . $codeUpper . '/' . $langUpper;
$archiveSub = $archiveBase . '/' . $codeUpper . '/' . $langUpper;
foreach([$targetDir,$archiveSub] as $dir){
    if(!is_dir($dir)) mkdir($dir, 0775, true);
}

$fname = $codeUpper.'__'.$langUpper.'__v'.date('Ymd_His').'.pdf';
$destRel = $docsRel.'/'.$codeUpper.'/'.$langUpper.'/'.$fname;
$destAbs = $docsBase.'/'.$codeUpper.'/'.$langUpper.'/'.$fname;
if(!move_uploaded_file($_FILES['file']['tmp_name'], $destAbs)) die('Upload fallito');

$exists = db_one($conn, "SELECT Id, FilePath FROM dbo.AccessoryDoc WHERE AccessoryId=? AND LangCode=?", [$accId, $langUpper]);
if($exists && !empty($exists['FilePath'])){
    $oldPath = (string)$exists['FilePath'];
    $oldAbs = $oldPath;
    if (!preg_match('~^[A-Za-z]:[\\\\/]|^/|^\\\\~', $oldPath)) { $oldAbs = __DIR__ . '/' . ltrim($oldPath, '/\\'); }
    if (is_file($oldAbs)) {
        $basename = basename($oldAbs);
        @rename($oldAbs, $archiveSub.'/'.$basename);
    }
}

$hash = hash_file('sha256', $destAbs);
$size = filesize($destAbs);

if($exists){
    db_exec($conn, "UPDATE dbo.AccessoryDoc SET FilePath=?, FileHash=?, FileSize=?, UpdatedAt=SYSUTCDATETIME() WHERE Id=?",
        [$destRel, $hash, $size, (int)$exists['Id']]);
} else {
    db_exec($conn, "INSERT INTO dbo.AccessoryDoc(AccessoryId,LangCode,FilePath,FileHash,FileSize) VALUES(?,?,?,?,?)",
        [$accId, $langUpper, $destRel, $hash, $size]);
}
header('Location: docs_audit.php');
<?php
require 'db.php'; require 'functions.php';

$inboxDir    = env_path('DOCS_INBOX_DIR','docs_inbox');
$docsSetting = env_value('DOCS_DIR','docs');
$docsBase    = env_path('DOCS_DIR','docs');
$docsIsAbs   = preg_match('~^[A-Za-z]:[\\\\/]|^/|^\\\\~', $docsSetting);
$docsRel     = $docsIsAbs ? rtrim($docsBase,'/\\') : trim($docsSetting,'/\\');
if($docsRel === '') $docsRel = 'docs';
$archiveBase = env_path('DOCS_ARCHIVE_DIR','docs_archive');
foreach([$inboxDir,$docsBase,$archiveBase] as $dir){ if(!is_dir($dir)) mkdir($dir, 0775, true); }

$accRows = db_all($conn, "SELECT Id, UPPER(Code) AS Code FROM dbo.Accessory WHERE Active=1");
$accMap  = [];
foreach ($accRows as $r) { $accMap[$r['Code']] = (int)$r['Id']; }
$langRows = db_all($conn, "SELECT UPPER(Code) AS Code FROM dbo.Language");
$langs = array_column($langRows, 'Code');

$res = ['imported'=>[], 'skipped'=>[], 'errors'=>[]];

$files = glob($inboxDir . '/*.pdf', GLOB_NOSORT) ?: [];
foreach ($files as $file) {
  $name = basename($file);
  if (!preg_match('/^AV_([A-Z0-9_-]+)_([A-Z]{2,5})\.pdf$/i', $name, $m)) {
    $res['skipped'][] = [$name, 'nome non conforme'];
    continue;
  }
  $accCode = strtoupper($m[1]);
  $lang    = strtoupper($m[2]);

  if (!isset($accMap[$accCode])) { $res['skipped'][] = [$name, 'accessorio sconosciuto']; continue; }
  if (!in_array($lang, $langs, true)) { $res['skipped'][] = [$name, 'lingua sconosciuta']; continue; }

  $accDir = $docsBase . '/' . $accCode . '/' . $lang;
  $archDir = $archiveBase . '/' . $accCode . '/' . $lang;
  foreach ([$accDir, $archDir] as $dir) { if (!is_dir($dir)) mkdir($dir, 0775, true); }

  $destName = $accCode . '__' . $lang . '__v' . date('Ymd_His') . '.pdf';
  $dest     = $accDir . '/' . $destName;
  $destRel  = $docsRel . '/' . $accCode . '/' . $lang . '/' . $destName;

  if (!rename($file, $dest)) { $res['errors'][] = [$name, 'spostamento fallito']; continue; }

  $hash  = hash_file('sha256', $dest);
  $size  = filesize($dest);
  $accId = $accMap[$accCode];

  $exists = db_one($conn, "SELECT Id, FilePath FROM dbo.AccessoryDoc WHERE AccessoryId=? AND LangCode=?", [$accId, $lang]);
  if ($exists && !empty($exists['FilePath'])) {
    $oldPath = (string)$exists['FilePath'];
    $oldAbs = $oldPath;
    if (!preg_match('~^[A-Za-z]:[\\\\/]|^/|^\\\\~', $oldPath)) { $oldAbs = __DIR__ . '/' . ltrim($oldPath, '/\\'); }
    if (is_file($oldAbs)) {
      $basename = basename($oldAbs);
      @rename($oldAbs, $archDir.'/'.$basename);
    }
  }

  if ($exists) {
    db_exec($conn, "UPDATE dbo.AccessoryDoc SET FilePath=?, FileHash=?, FileSize=?, UpdatedAt=SYSUTCDATETIME() WHERE Id=?",
      [$destRel, $hash, $size, (int)$exists['Id']]);
  } else {
    db_exec($conn, "INSERT INTO dbo.AccessoryDoc(AccessoryId,LangCode,FilePath,FileHash,FileSize) VALUES(?,?,?,?,?)",
      [$accId, $lang, $destRel, $hash, $size]);
  }

  $res['imported'][] = [$name, $destRel];
}

$wantJson = (isset($_GET['format']) && $_GET['format'] === 'json');
if ($wantJson) {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode([
    'imported' => $res['imported'],
    'skipped'  => $res['skipped'],
    'errors'   => $res['errors'],
    'counts'   => [
      'imported' => count($res['imported']),
      'skipped'  => count($res['skipped']),
      'errors'   => count($res['errors']),
    ],
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

$qs = http_build_query([
  'scan_done' => 1,
  'imp'       => count($res['imported']),
  'sk'        => count($res['skipped']),
  'err'       => count($res['errors']),
]);
header('Location: docs_audit.php?'.$qs);
exit;
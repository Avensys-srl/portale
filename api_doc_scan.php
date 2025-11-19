<?php
require 'db.php'; require 'functions.php';

// Configurazione cartelle
$inbox = __DIR__ . '/docs_inbox'; // dove vengono depositati i PDF nominati correttamente
$outDir = __DIR__ . '/docs';      // cartella finale usata anche da api_doc_upload
$archiveDir = __DIR__ . '/docs_archive'; // storico versioni precedenti
foreach ([$inbox, $outDir, $archiveDir] as $dir) {
  if (!is_dir($dir)) { mkdir($dir, 0775, true); }
}

// Recupero accessori attivi e lingue
$accRows = db_all($conn, "SELECT Id, UPPER(Code) AS Code FROM dbo.Accessory WHERE Active=1");
$accMap  = [];
foreach ($accRows as $r) { $accMap[$r['Code']] = (int)$r['Id']; }
$langRows = db_all($conn, "SELECT UPPER(Code) AS Code FROM dbo.Language");
$langs = array_column($langRows, 'Code');

$res = ['imported'=>[], 'skipped'=>[], 'errors'=>[]];

// Regola: AV_<ACC>_<LANG>.pdf (case-insensitive)
$files = glob($inbox . '/*.pdf', GLOB_NOSORT) ?: [];
foreach ($files as $file) {
  $name = basename($file);
  if (!preg_match('/^AV_([A-Z0-9_-]+)_([A-Z]{2,5})\.pdf$/i', $name, $m)) {
    $res['skipped'][] = [$name, 'nome non conforme'];
    continue;
  }
  $accCode = strtoupper($m[1]);
  $lang    = strtoupper($m[2]);

  if (!isset($accMap[$accCode])) {
    $res['skipped'][] = [$name, 'accessorio sconosciuto'];
    continue;
  }
  if (!in_array($lang, $langs, true)) {
    $res['skipped'][] = [$name, 'lingua sconosciuta'];
    continue;
  }

  $accDir = $outDir . '/' . $accCode . '/' . $lang;
  $archDir = $archiveDir . '/' . $accCode . '/' . $lang;
  foreach ([$accDir, $archDir] as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0775, true);
  }

  // Nome con timestamp per evitare collisioni nello stesso giorno
  $destName = $accCode . '__' . $lang . '__v' . date('Ymd_His') . '.pdf';
  $dest     = $accDir . '/' . $destName;
  $destRel  = 'docs/' . $accCode . '/' . $lang . '/' . $destName;

  if (!rename($file, $dest)) {
    $res['errors'][] = [$name, 'spostamento fallito'];
    continue;
  }

  $hash  = hash_file('sha256', $dest);
  $size  = filesize($dest);
  $accId = $accMap[$accCode];

  // Archivio eventuale file precedente
  $exists = db_one($conn, "SELECT Id, FilePath FROM dbo.AccessoryDoc WHERE AccessoryId=? AND LangCode=?", [$accId, $lang]);
  if ($exists && !empty($exists['FilePath'])) {
    $oldPath = (string)$exists['FilePath'];
    $oldAbs = $oldPath;
    if (!preg_match('~^[A-Za-z]:\\\\|^/|^\\\\~', $oldPath)) { $oldAbs = __DIR__ . '/' . ltrim($oldPath, '/\\'); }
    if (is_file($oldAbs)) {
      $basename = basename($oldAbs);
      @rename($oldAbs, $archDir.'/'.$basename);
    }
  }

  // upsert AccessoryDoc
  if ($exists) {
    db_exec($conn, "UPDATE dbo.AccessoryDoc SET FilePath=?, FileHash=?, FileSize=?, UpdatedAt=SYSUTCDATETIME() WHERE Id=?",
      [$destRel, $hash, $size, (int)$exists['Id']]);
  } else {
    db_exec($conn, "INSERT INTO dbo.AccessoryDoc(AccessoryId,LangCode,FilePath,FileHash,FileSize) VALUES(?,?,?,?,?)",
      [$accId, $lang, $destRel, $hash, $size]);
  }

  $res['imported'][] = [$name, $destRel];
}

// Output: se format=json ritorna JSON, altrimenti redirect con contatori
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

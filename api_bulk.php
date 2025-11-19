<?php
// --- OUTPUT HARDENING ---
while (ob_get_level()) { ob_end_clean(); }
header_remove('X-Powered-By');
header('Content-Type: application/json; charset=utf-8');
http_response_code(200);
ini_set('display_errors', '0');   // evita NOTICE/Warning nell'output JSON
error_reporting(E_ALL);

require 'db.php';
require 'functions.php';

function jexit(array $arr){
  echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  exit;
}

// --- INPUT ---
$action  = $_POST['action'] ?? '';
$unitIds = $_POST['unit_ids'] ?? [];
$accIds  = $_POST['accessory_ids'] ?? [];

if (!in_array($action, ['include','exclude','inherit'], true)) {
  jexit(['ok'=>false,'msg'=>'Azione non valida']);
}
if (!count($unitIds) || !count($accIds)) {
  jexit(['ok'=>false,'msg'=>'Seleziona almeno un’unità e un accessorio']);
}

// CSV sicuri
$csvU = implode(',', array_map('intval', $unitIds));
$csvA = implode(',', array_map('intval', $accIds));
$user = 'webapp';

// --- CALL SP ---
$sql = "EXEC dbo.sp_BulkUnitAccessory_CSV @UnitCsv=?, @AccCsv=?, @Action=?, @User=?";
$params = [$csvU, $csvA, $action, $user];

$stmt = @sqlsrv_query($conn, $sql, $params);
if (!$stmt) {
  $errs = sqlsrv_errors();
  $msg  = 'Errore esecuzione SP';
  if ($errs && isset($errs[0]['message'])) $msg .= ': '.$errs[0]['message'];
  jexit(['ok'=>false,'msg'=>$msg,'detail'=>$errs]);
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);

// Se la SP non ha prodotto righe (caso limite)
if (!$row) {
  jexit(['ok'=>true,'msg'=>'Operazione eseguita.']);
}

// Normalizzazione colonne contatori (gestisce tutte le versioni della SP)
$units = (int)($row['UnitsSelected']      ?? $row['UnitsValid']      ?? $row['UnitsInput']      ?? 0);
$accs  = (int)($row['AccessoriesSelected']?? $row['AccessoriesValid']?? $row['AccessoriesInput']?? 0);
$act   = (string)($row['ActionApplied']    ?? $action);

// Messaggistica coerente
if ($units === 0 || $accs === 0) {
  $msg = "Nessuna combinazione valida: unità=$units, accessori=$accs";
  jexit(['ok'=>true,'msg'=>$msg,'result'=>$row]);
}

$msg = sprintf("Applicata %s su %d unità × %d accessori", strtoupper($act), $units, $accs);
jexit(['ok'=>true,'msg'=>$msg,'result'=>$row]);

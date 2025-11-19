<?php
require 'db.php'; require 'functions.php';
header('Content-Type: application/json');
$uid = (int)($_POST['unit_id'] ?? 0);
$aid = (int)($_POST['accessory_id'] ?? 0);
$act = $_POST['action'] ?? '';
$user = 'webapp';

$stmt = sqlsrv_query($conn, "EXEC dbo.sp_ToggleUnitAccessory @UnitId=?, @AccessoryId=?, @Action=?, @User=?",
  [$uid, $aid, $act, $user]);
if(!$stmt){ http_response_code(500); echo json_encode(['error'=>sqlsrv_errors()]); exit; }
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
sqlsrv_free_stmt($stmt);
echo json_encode($row ?: ['EffectiveEnabled'=>null,'OverrideRelation'=>null]);

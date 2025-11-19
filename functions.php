<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function db_all($conn, $sql, $params=[]){
    $stmt = sqlsrv_query($conn, $sql, $params, ["Scrollable"=>"forward"]);
    if(!$stmt) throw new RuntimeException(print_r(sqlsrv_errors(), true));
    $rows=[];
    while($r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)){ $rows[]=$r; }
    sqlsrv_free_stmt($stmt);
    return $rows;
}
function db_one($conn, $sql, $params=[]){
    $stmt = sqlsrv_query($conn, $sql, $params);
    if(!$stmt) throw new RuntimeException(print_r(sqlsrv_errors(), true));
    $r = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    sqlsrv_free_stmt($stmt);
    return $r ?: null;
}
function db_exec($conn, $sql, $params=[]){
    $stmt = sqlsrv_query($conn, $sql, $params);
    if(!$stmt) throw new RuntimeException(print_r(sqlsrv_errors(), true));
    sqlsrv_free_stmt($stmt);
    return true;
}

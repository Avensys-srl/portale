<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

if(!function_exists('env_value')){
    function env_value($key, $default=null){
        if(function_exists('env')){
            $val = env($key, $default);
        } else {
            $envVal = getenv($key);
            $val = ($envVal !== false) ? $envVal : $default;
        }
        return ($val === null || $val === '') ? $default : $val;
    }
}

if(!function_exists('env_path')){
    function env_path($key, $default){
        $path = env_value($key, $default);
        if(!$path) { $path = $default; }
        $path = trim($path);
        if($path === '') { $path = $default; }
        if(preg_match('~^[A-Za-z]:[\\\\/]|^/|^\\\\~', $path)){
            return rtrim($path, "/\\");
        }
        return rtrim(__DIR__ . '/' . trim($path,'/\\'), "/\\");
    }
}

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
if(!function_exists('env_url')){
    function env_url($key, $default){
        $url = env_value($key, $default);
        if(!$url) { $url = $default; }
        $url = trim($url);
        if($url === '') { $url = $default; }
        if($url === '') { return ''; }
        return rtrim($url, '/');
    }
}

if(!function_exists('doc_url_from_path')){
    function doc_url_from_path($path){
        $path = (string)$path;
        if($path === '') return '';
        $normalized = str_replace('\\','/',$path);
        $docsBase = str_replace('\\','/', env_path('DOCS_DIR','docs'));
        $docsUrl  = env_url('DOCS_URL','docs');
        if($docsUrl === '') return $normalized;

        if(stripos($normalized, $docsBase) === 0){
            $suffix = substr($normalized, strlen($docsBase));
            return rtrim($docsUrl,'/') . $suffix;
        }
        if($normalized[0] !== '/' && !preg_match('~^[a-z]+://~i', $normalized)){
            return rtrim($docsUrl,'/') . '/' . ltrim($normalized, '/');
        }
        return $normalized;
    }
}
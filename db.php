<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Carica .env (key=value, commenti con #)
function env($key, $default = null) {
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        $path = __DIR__.'/.env';
        if (is_file($path)) {
            foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                if (strpos(ltrim($line), '#') === 0) continue;
                $parts = explode('=', $line, 2);
                if (count($parts) === 2) {
                    $cache[$parts[0]] = $parts[1];
                }
            }
        }
    }
    return array_key_exists($key, $cache) ? $cache[$key] : $default;
}

// Connessione SQL Server con config da .env
$dbHost   = env('DB_HOST', 'SERVER01\\sqlserver');
$dbPort   = env('DB_PORT', '1433');
$dbName   = env('DB_NAME', 'CLDataCentral2');
$dbUser   = env('DB_USER', 'sa');
$dbPass   = env('DB_PASS', 'P4ssword');
$dbCharset= env('DB_CHARSET', 'UTF-8');
$dbLoginT = (int)env('DB_LOGIN_TIMEOUT', 5);

$serverName = $dbHost . ($dbPort ? ', ' . $dbPort : '');
$connectionInfo = [
  "Database" => $dbName,
  "UID" => $dbUser,
  "PWD" => $dbPass,
  "CharacterSet" => $dbCharset,
  "LoginTimeout" => $dbLoginT,
];

$conn = sqlsrv_connect($serverName, $connectionInfo);
if ($conn === false) {
    die("DB connection failed: " . print_r(sqlsrv_errors(), true));
}

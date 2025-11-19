<?php
// Endpoint USD->EUR con caching 24h usando freecurrencyapi (read-only)
// Richiede FREECURRENCYAPI_KEY in .env

// Minimal env loader
function env_local($key, $default = null) {
  static $cache = null;
  if ($cache === null) {
    $cache = [];
    $path = __DIR__ . '/.env';
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

header('Content-Type: application/json; charset=utf-8');

$apiKey = env_local('FREECURRENCYAPI_KEY', '');
if ($apiKey === '') {
  http_response_code(500);
  echo json_encode(['ok'=>false, 'error'=>'API key mancante (APILAYER_KEY in .env)']);
  exit;
}

$amount = isset($_GET['amount']) ? (float)$_GET['amount'] : 1.0;
if ($amount <= 0) $amount = 1.0;

$cacheFile = __DIR__ . '/usdeur_cache.json';
$maxAge = 86400; // 24 ore
$now = time();

// Usa cache se presente e fresca
if (is_file($cacheFile)) {
  $cache = json_decode(file_get_contents($cacheFile), true);
  if (is_array($cache) && isset($cache['fetched_at'], $cache['rate']) && ($now - (int)$cache['fetched_at'] < $maxAge)) {
    $rate = (float)$cache['rate'];
    echo json_encode([
      'ok'=>true,
      'source'=>'cache',
      'rate'=>$rate,
      'result'=>$amount * $rate,
      'fetched_at'=>$cache['fetched_at'],
      'cache_age_sec'=>$now - (int)$cache['fetched_at'],
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
  }
}

$url = "https://api.freecurrencyapi.com/v1/latest?apikey={$apiKey}&currencies=EUR";

$ch = curl_init();
curl_setopt_array($ch, [
  CURLOPT_URL => $url,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT => 10,
]);
$raw = curl_exec($ch);
if ($raw === false) {
  http_response_code(502);
  echo json_encode(['ok'=>false, 'error'=>'curl_error: '.curl_error($ch)]);
  curl_close($ch);
  exit;
}
curl_close($ch);

$data = json_decode($raw, true);
$rate = $data['data']['EUR'] ?? null;
if (!$data || !is_numeric($rate)) {
  http_response_code(502);
  $msg = isset($data['message']) ? $data['message'] : 'conversione non riuscita';
  echo json_encode(['ok'=>false, 'error'=>$msg, 'raw'=>$data]);
  exit;
}

$cacheData = [
  'fetched_at' => $now,
  'rate' => (float)$rate,
  'source' => 'freecurrencyapi',
  'raw' => $data,
];
@file_put_contents($cacheFile, json_encode($cacheData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

$resp = [
  'ok' => true,
  'source' => 'live',
  'rate' => (float)$rate,
  'result' => $amount * (float)$rate,
  'fetched_at' => $now,
];
echo json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

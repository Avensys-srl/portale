<?php
require 'db.php';
require 'functions.php';

header('Content-Type: application/json; charset=utf-8');

$modelId = isset($_GET['model_id']) ? (int)$_GET['model_id'] : 0;
$lang    = strtoupper(trim($_GET['lang'] ?? ''));

if ($modelId <= 0 || $lang === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Parametri mancanti: model_id e lang sono obbligatori']);
    exit;
}

// Lingua valida
$langRow = db_one($conn, "SELECT Code FROM dbo.Language WHERE UPPER(Code)=?", [$lang]);
if (!$langRow) {
    http_response_code(400);
    echo json_encode(['error' => 'Lingua non valida']);
    exit;
}

// Modello valido (e con ModRec valorizzato)
$modelRow = db_one($conn, "SELECT Id FROM dbo.CLHeatRecoveryModels WHERE Id=? AND ModRec IS NOT NULL", [$modelId]);
if (!$modelRow) {
    http_response_code(404);
    echo json_encode(['error' => 'Modello non trovato']);
    exit;
}

// Accessori abilitati per il modello, con eventuale documento nella lingua richiesta
$sql = "SELECT a.Id, a.Code, a.NameKey, d.FilePath
          FROM dbo.vw_UnitAccessoryMatrix m
          JOIN dbo.Accessory a ON a.Id = m.AccessoryId
          LEFT JOIN dbo.AccessoryDoc d ON d.AccessoryId = a.Id AND d.LangCode = ?
         WHERE m.UnitId = ? AND m.EffectiveEnabled = 1 AND a.Active = 1
         ORDER BY a.Code";
$rows = db_all($conn, $sql, [$lang, $modelId]);

$fallbackLang = 'EN';
$items = [];
foreach ($rows as $r) {
    $requestedPath = $r['FilePath'] ?? '';
    $filePath = $requestedPath;
    $docLang  = $filePath ? $lang : null;

    // Fallback a EN se la lingua richiesta non ha documento
    if (!$filePath && $lang !== $fallbackLang) {
        $docEn = db_one(
            $conn,
            "SELECT TOP 1 FilePath FROM dbo.AccessoryDoc WHERE AccessoryId=? AND LangCode=?",
            [(int)$r['Id'], $fallbackLang]
        );
        if ($docEn && !empty($docEn['FilePath'])) {
            $filePath = $docEn['FilePath'];
            $docLang  = $fallbackLang;
        }
    }

    $url = $filePath ? doc_url_from_path($filePath) : null;

    $items[] = [
        'accessory_id' => (int)$r['Id'],
        'code'         => $r['Code'],
        'name_key'     => $r['NameKey'],
        'lang'         => $lang,
        'doc_lang'     => $docLang,
        'doc_url'      => $url ?: null,
        'file_path'    => $filePath ?: null,
        'file_name'    => $filePath ? basename($filePath) : null,
        'has_doc'      => $filePath ? true : false,
    ];
}

echo json_encode([
    'model_id' => $modelId,
    'lang'     => $lang,
    'count'    => count($items),
    'items'    => $items,
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

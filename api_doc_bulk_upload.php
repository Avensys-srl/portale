<?php
require 'db.php'; // per uniformità (anche se non servirebbe la connessione qui)
require 'functions.php';

// Cartella inbox dove lo scan andrà a prendere i file
$inbox = env_path('DOCS_INBOX_DIR','docs_inbox');
if (!is_dir($inbox)) mkdir($inbox, 0775, true);

if (empty($_FILES['files'])) {
  header('Location: docs_audit.php?err=nofile');
  exit;
}

$count = 0;
$errors = [];

$names = $_FILES['files']['name'];
$tmps  = $_FILES['files']['tmp_name'];
$errs  = $_FILES['files']['error'];
$sizes = $_FILES['files']['size'];

$total = is_array($names) ? count($names) : 0;
for ($i=0; $i<$total; $i++){
  if ($errs[$i] !== UPLOAD_ERR_OK) { $errors[] = $names[$i].' (errore upload)'; continue; }
  $name = preg_replace('/[^A-Za-z0-9_.-]/', '_', $names[$i]);
  if (strtolower(substr($name, -4)) !== '.pdf') { $errors[] = $name.' (non PDF)'; continue; }
  $dest = $inbox . '/' . $name;
  if (!move_uploaded_file($tmps[$i], $dest)) { $errors[] = $name.' (spostamento fallito)'; continue; }
  $count++;
}

// Dopo l'upload massivo esegui direttamente lo scan
$qs = http_build_query([
  'uploaded' => $count,
  'u_err'    => count($errors),
]);
header('Location: api_doc_scan.php?'.$qs);
exit;

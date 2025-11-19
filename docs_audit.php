<?php
require 'db.php'; require 'functions.php'; require 'layout.php';
page_header('Audit Documenti Accessori','cldc');

$langs = db_all($conn, "SELECT Code,Name FROM dbo.Language ORDER BY Code");
$missing = db_all($conn, "EXEC dbo.sp_DocCoverageMissing @OnlyActive=1");

echo '<div class="toolbar">
  <form action="api_doc_scan.php" method="post" style="display:inline">
    <button class="btn" type="submit">Riesegui scan</button>
  </form>
  <form id="bulkUploadForm" action="api_doc_bulk_upload.php" method="post" enctype="multipart/form-data" style="display:inline">
    <input id="bulkFiles" type="file" name="files[]" accept="application/pdf" multiple style="display:none">
    <button class="btn primary" type="button" id="bulkUploadBtn">Upload massivo</button>
  </form>
</div>';

echo '<table><tr><th>Accessory</th>';
foreach($langs as $l) echo '<th>'.h($l['Code']).'</th>';
echo '</tr>';

$accs = db_all($conn, "SELECT Id,Code FROM dbo.Accessory WHERE Active=1 ORDER BY Code");
foreach($accs as $a){
  echo '<tr><td>'.h($a['Code']).'</td>';
  foreach($langs as $l){
    $doc = db_one($conn, "SELECT TOP 1 FilePath,UpdatedAt FROM dbo.AccessoryDoc WHERE AccessoryId=? AND LangCode=?",
      [$a['Id'], $l['Code']]);
    $inputId = 'file_'.(int)$a['Id'].'_'.h($l['Code']);

        if($doc){
      $prettyPath = $doc["FilePath"];
      if (preg_match('~[\\/](docs[\\/].*)$~i', $prettyPath, $m)) { $prettyPath = $m[1]; }
      echo '<td><span class="checkmark" aria-label="presente">&#10003;</span> <span class="muted">'.h($prettyPath).'</span>
            <div class="row" style="gap:6px;flex-wrap:wrap">
              <form action="api_doc_upload.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="acc_id" value="'.(int)$a['Id'].'">
                <input type="hidden" name="lang" value="'.h($l['Code']).'">
                <input id="'.$inputId.'" type="file" name="file" accept="application/pdf" required style="display:none">
                <button class="btn file-trigger" type="button" data-file="'.$inputId.'">Replace</button>
              </form>
              <form action="api_doc_delete.php" method="post" onsubmit="return confirm(\'Eliminare e archiviare il documento?\');">
                <input type="hidden" name="acc_id" value="'.(int)$a['Id'].'">
                <input type="hidden" name="lang" value="'.h($l['Code']).'">
                <button class="btn danger" type="submit">Elimina</button>
              </form>
            </div></td> ';
    } else {
      echo '<td>--<form action="api_doc_upload.php" method="post" enctype="multipart/form-data">
              <input type="hidden" name="acc_id" value="'.(int)$a['Id'].'">
              <input type="hidden" name="lang" value="'.h($l['Code']).'">
              <input id="'.$inputId.'" type="file" name="file" accept="application/pdf" required style="display:none">
              <button class="btn primary file-trigger" type="button" data-file="'.$inputId.'">Upload</button>
            </form></td>';
    }
  }
  echo '</tr>';
}
echo '</table>';
?>
<script>
// Trigger selezione file per i form individuali
document.querySelectorAll('.file-trigger').forEach(btn => {
  const input = document.getElementById(btn.dataset.file);
  if (!input) return;
  btn.addEventListener('click', () => input.click());
  input.addEventListener('change', () => {
    if (input.files.length) {
      btn.closest('form')?.submit();
    }
  });
});

// Upload massivo (più PDF insieme)
const bulkBtn = document.getElementById('bulkUploadBtn');
const bulkInput = document.getElementById('bulkFiles');
const bulkForm = document.getElementById('bulkUploadForm');
bulkBtn?.addEventListener('click', () => bulkInput?.click());
bulkInput?.addEventListener('change', () => {
  if (bulkInput.files.length) bulkForm.submit();
});
</script>
<?php
page_footer();

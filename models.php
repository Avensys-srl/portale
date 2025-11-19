<?php
require 'db.php';
require 'functions.php';
require 'layout.php';
require 'CLDC_class.php';

$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
if ($limit < 1) $limit = 1;
if ($limit > 500) $limit = 500;

$seriesId = isset($_GET['series_id']) ? (int)$_GET['series_id'] : null;

// Clonazione modello (usa CLDC::add_newmodel)
$cloneMsg = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clone_action'])) {
  $oldCode = trim($_POST['old_code'] ?? '');
  $newCode = trim($_POST['new_code'] ?? '');
  $newName = trim($_POST['new_name'] ?? '');
  $newSerie = $_POST['new_serie'] !== '' ? (int)$_POST['new_serie'] : null;
  $newLayout = $_POST['new_layout'] !== '' ? (int)$_POST['new_layout'] : null;

  if ($oldCode === '' || $newCode === '' || $newName === '') {
    $cloneMsg = ['type'=>'error','text'=>'Compila codice origine, nuovo codice e nome.'];
  } else {
    try {
      $cldc = new CLDC($conn);
      // add_newmodel usa il nuovo codice anche come nome se non diversamente richiesto
      $cldc->add_newmodel($newCode, $oldCode, $newSerie, $newLayout);
      // aggiorna nome se diverso dal codice
      if ($newName !== $newCode) {
        db_exec($conn, "UPDATE dbo.CLHeatRecoveryModels SET Name=? WHERE Code=?", [$newName, $newCode]);
      }
      $cloneMsg = ['type'=>'ok','text'=>"Modello '$oldCode' clonato in '$newCode'"];
    } catch (Throwable $e) {
      $cloneMsg = ['type'=>'error','text'=>'Clonazione fallita: '.$e->getMessage()];
    }
  }
}

$cldc = new CLDC($conn);
$series = array_filter($cldc->serie(), function($s){
  $desc = isset($s['feature']) ? (string)$s['feature'] : '';
  return strpos($desc, '|') !== false;
});
$layouts = $cldc->layout();
$codes = db_all($conn, "SELECT Code FROM dbo.CLHeatRecoveryModels WHERE ModRec IS NOT NULL ORDER BY Code");

$sql = "SELECT TOP $limit * FROM dbo.CLHeatRecoveryModels WHERE ModRec IS NOT NULL";
$params = [];
if ($seriesId) {
  $sql .= " AND IdSerie = ?";
  $params[] = $seriesId;
}
$sql .= " ORDER BY Id";

$rows = db_all($conn, $sql, $params);
$columns = [];
if (!empty($rows)) {
  $columns = array_keys($rows[0]);
}

// Map per mostrare nomi al posto degli ID
$seriesMap = [];
foreach (db_all($conn, "SELECT Id, Name, Code FROM dbo.CLSeries") as $s) {
  $seriesMap[(int)$s['Id']] = trim((string)($s['Name'] ?? '')) ?: trim((string)($s['Code'] ?? ''));
}
$layoutMap = [];
foreach (db_all($conn, "SELECT Id, TextCode, Name FROM dbo.CLEnumItems WHERE IdEnum=4") as $l) {
  $label = trim((string)($l['Name'] ?? ''));
  if ($label === '') { $label = trim((string)($l['TextCode'] ?? '')); }
  $layoutMap[(int)$l['Id']] = $label;
}

page_header('Modelli CLDC','cldc');
?>
<div class="toolbar">
  <form method="get" class="row">
    <label>Serie</label>
    <select name="series_id">
      <option value="">Tutte</option>
      <?php foreach($series as $s){
        $sid = (int)$s['id'];
        $sel = ($seriesId && $seriesId == $sid) ? ' selected' : '';
        $name = trim((string)($s['name'] ?? ''));
        if ($name === '') { $name = trim((string)($s['code'] ?? 'Serie '.$sid)); }
        echo '<option value="'.$sid.'"'.$sel.'>'.h($name).'</option>';
      }?>
    </select>
    <label>Limite</label>
    <input type="number" name="limit" value="<?= (int)$limit ?>" min="1" max="500" style="width:80px">
    <button class="btn" type="submit">Filtra</button>
  </form>
</div>

<?php if($cloneMsg){ ?>
  <div class="muted" style="margin:8px 0; color:<?= $cloneMsg['type']==='ok' ? '#2f855a' : '#b43434' ?>;">
    <?= h($cloneMsg['text']) ?>
  </div>
<?php } ?>

<details style="margin:10px 0;">
  <summary style="cursor:pointer;">Clona un modello</summary>
  <form method="post" class="row" style="margin-top:8px; flex-wrap:wrap; gap:10px;">
    <input type="hidden" name="clone_action" value="1">
    <label>Da codice</label>
    <select name="old_code" required>
      <option value="">Seleziona...</option>
      <?php foreach($codes as $c){
        $code = trim((string)($c['Code'] ?? ''));
        if ($code === '') continue;
        echo '<option value="'.h($code).'">'.h($code).'</option>';
      }?>
    </select>
    <label>Nuovo codice</label>
    <input type="text" name="new_code" placeholder="Nuovo codice" required>
    <label>Nuovo nome</label>
    <input type="text" name="new_name" placeholder="Nuovo nome" required>
    <label>Serie (opz.)</label>
    <select name="new_serie">
      <option value="">Stessa serie</option>
      <?php foreach($series as $s){
        $sid = (int)$s['id'];
        $name = trim((string)($s['name'] ?? ''));
        if ($name === '') { $name = trim((string)($s['code'] ?? 'Serie '.$sid)); }
        echo '<option value="'.$sid.'">'.h($name).'</option>';
      }?>
    </select>
    <label>Layout (opz.)</label>
    <select name="new_layout">
      <option value="">Stesso layout</option>
      <?php foreach($layouts as $l){
        echo '<option value="'.(int)$l['id'].'">'.h($l['id'].' - '.$l['TextCode']).'</option>';
      }?>
    </select>
    <button class="btn primary" type="submit">Clona</button>
  </form>
</details>

<?php if(empty($rows)){ ?>
  <p class="muted">Nessun modello trovato.</p>
<?php } else { ?>
  <div style="overflow:auto; max-width:100%;">
    <table>
      <tr>
        <?php foreach($columns as $c){ echo '<th>'.h($c).'</th>'; } ?>
      </tr>
      <?php foreach($rows as $r){ ?>
        <tr>
          <?php foreach($columns as $c){
            $v = $r[$c];
            if ($c === 'IdSerie') {
              $v = $seriesMap[(int)$v] ?? $v;
            } elseif ($c === 'IdAeraulicConnection') {
              $v = $layoutMap[(int)$v] ?? $v;
            }
            if ($v instanceof DateTime) {
              $v = $v->format('Y-m-d H:i:s');
            }
            echo '<td>'.h((string)$v).'</td>';
          } ?>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php } ?>

<?php page_footer(); ?>

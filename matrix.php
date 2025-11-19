<?php
require 'db.php'; require 'functions.php'; require 'layout.php';
page_header('Relazioni U/A','cldc');

// Filtri base
$seriesId = isset($_GET['series_id']) ? (int)$_GET['series_id'] : null;
$familyId = isset($_GET['family_id']) ? (int)$_GET['family_id'] : null;

// Carica Serie e Famiglie per filtri
$series = db_all(
  $conn,
  "SELECT s.Id, s.Name
     FROM dbo.CLSeries s
     JOIN dbo.CLHeatRecoveryModels u ON u.IdSerie = s.Id
    WHERE u.ModRec IS NOT NULL
  GROUP BY s.Id, s.Name
  ORDER BY s.Id"
);
$fams = db_all($conn, "SELECT Id,Name FROM dbo.AccessoryFamily ORDER BY Name");

// Colonne (accessori)
$accSql = "SELECT a.Id,a.Code,f.Name AS FamilyName 
             FROM dbo.Accessory a 
             JOIN dbo.AccessoryFamily f ON f.Id=a.FamilyId 
            WHERE a.Active=1";
$params=[];
if($familyId){ $accSql.=" AND a.FamilyId=?"; $params[]=$familyId; }
$accSql.=" ORDER BY f.Name,a.Code";
$accessories = db_all($conn, $accSql, $params);

// Righe (unità)
$unitSql = "SELECT u.Id, u.Code
              FROM dbo.CLHeatRecoveryModels u
             WHERE u.ModRec IS NOT NULL";
$uparams = [];
if ($seriesId){
  $unitSql .= " AND u.IdSerie = ?";
  $uparams[] = $seriesId;
}
$unitSql .= " ORDER BY u.Code";
$units = db_all($conn, $unitSql, $uparams);

// Recupero stato matrice tramite view (per il set corrente)
$idsU = implode(',', array_map(function($r){ return (int)$r['Id']; }, $units));
$idsU = ($idsU === '') ? 'NULL' : $idsU;

$idsA = implode(',', array_map(function($r){ return (int)$r['Id']; }, $accessories));
$idsA = ($idsA === '') ? 'NULL' : $idsA;

$matrix = [];
if($idsU!=='NULL' && $idsA!=='NULL'){
  $sql = "SELECT UnitId,AccessoryId,EffectiveEnabled,OverrideRelation
            FROM dbo.vw_UnitAccessoryMatrix
           WHERE UnitId IN ($idsU) AND AccessoryId IN ($idsA)";
  $rows = db_all($conn, $sql);
  foreach($rows as $r){
    $matrix[$r['UnitId']][$r['AccessoryId']] = $r;
  }
}
?>
<div class="sticky">
  <form method="get" class="row">
    <label>Serie</label>
    <select name="series_id">
      <option value="">Tutte</option>
      <?php foreach($series as $s){
        $sel = ($seriesId && $seriesId==$s['Id'])?' selected':'';
        $label = trim(($s['Name'] ?? ''));
        if ($label === '') { $label = 'Serie '.$s['Id']; }
        echo '<option value="'.(int)$s['Id'].'"'.$sel.'>'.h($s['Id'].' – '.$label).'</option>';
      }?>
    </select>
    <label>Famiglia</label>
    <select name="family_id">
      <option value="">Tutte</option>
      <?php foreach($fams as $f){
        $sel = ($familyId && $familyId==$f['Id'])?' selected':'';
        echo '<option value="'.(int)$f['Id'].'"'.$sel.'>'.h($f['Name']).'</option>';
      }?>
    </select>
    <button class="btn" type="submit">Filtra</button>
    <button class="btn" type="button" id="bulkInclude">Include</button>
    <button class="btn" type="button" id="bulkExclude">Escludi</button>
    <button class="btn" type="button" id="bulkInherit">Inherit</button>
  </form>
</div>

<div id="status" class="muted" style="margin:8px 0 12px 0;"></div>

<table id="matrix">
  <tr>
    <th>
      <label>
        <input type="checkbox" id="selAllUnits"> Unità
      </label>
    </th>
    <?php foreach($accessories as $a){ ?>
      <th>
        <label>
          <input type="checkbox" class="sel-acc" value="<?= (int)$a['Id'] ?>">
          <strong><?= h($a['Code']) ?></strong>
        </label>
        <div class="muted"><?= h($a['FamilyName']) ?></div>
      </th>
    <?php } ?>
  </tr>

  <?php foreach($units as $u): ?>
    <tr>
      <th>
        <label>
          <input type="checkbox" class="sel-unit" value="<?= (int)$u['Id'] ?>"> <?= h($u['Code']) ?>
        </label>
      </th>
      <?php foreach($accessories as $a):
        $cell = $matrix[$u['Id']][$a['Id']] ?? ['EffectiveEnabled'=>0,'OverrideRelation'=>null];
        $on  = (int)$cell['EffectiveEnabled']===1;
        $ovr = $cell['OverrideRelation'];
      ?>
      <td data-u="<?= (int)$u['Id'] ?>" data-a="<?= (int)$a['Id'] ?>">
        <button class="btn pill <?= $on?'on':'off' ?> toggle">
          <?= $on?'ON':'OFF' ?>
        </button>
        <?php if($ovr){ echo ' <span class="badge">'.h($ovr).'</span>'; } ?>
      </td>
      <?php endforeach; ?>
    </tr>
  <?php endforeach; ?>
</table>

<script>
// Calcolo dinamico endpoint (router-friendly)
const BASE = (() => {
  const href = window.location.href;
  return href.substring(0, href.lastIndexOf('/') + 1);
})();
const API_BULK   = BASE + 'api_bulk.php';
const API_TOGGLE = BASE + 'api_toggle.php';

// --- Toggle singolo ---
async function toggle(u,a,action){
  const fd = new FormData();
  fd.append('unit_id', u);
  fd.append('accessory_id', a);
  fd.append('action', action);
  const r = await fetch(API_TOGGLE, { method:'POST', body:fd });
  const raw = await r.text();
  let data;
  try { data = JSON.parse(raw); } catch { throw new Error('Non-JSON: ' + raw.slice(0,200)); }
  return data;
}

document.querySelectorAll('.toggle').forEach(btn=>{
  btn.addEventListener('click', async (e)=>{
    const td = e.target.closest('td');
    const u = td.dataset.u, a = td.dataset.a;
    let action = 'include';
    if (td.querySelector('.badge')?.textContent==='include') action='exclude';
    else if (td.querySelector('.badge')?.textContent==='exclude') action='inherit';
    const res = await toggle(u,a,action);
    if(res && 'EffectiveEnabled' in res){
      btn.classList.toggle('on', res.EffectiveEnabled==1);
      btn.classList.toggle('off', res.EffectiveEnabled!=1);
      btn.textContent = res.EffectiveEnabled==1 ? 'ON' : 'OFF';
      const b = td.querySelector('.badge');
      if(res.OverrideRelation){
        if(b) b.textContent=res.OverrideRelation;
        else {
          const span=document.createElement('span');
          span.className='badge';
          span.textContent=res.OverrideRelation;
          td.appendChild(span);
        }
      }else if(b){ b.remove(); }
    }
  });
});

// --- Master checkbox Unità ---
const selAllUnits = document.getElementById('selAllUnits');
function refreshSelAllUnits(){
  const boxes = Array.from(document.querySelectorAll('.sel-unit'));
  const total = boxes.length;
  const checked = boxes.filter(b=>b.checked).length;
  if (total === 0){ selAllUnits.checked = false; selAllUnits.indeterminate = false; return; }
  selAllUnits.checked = (checked === total);
  selAllUnits.indeterminate = (checked > 0 && checked < total);
}
selAllUnits?.addEventListener('change', e=>{
  const v = e.target.checked;
  document.querySelectorAll('.sel-unit').forEach(b=>{ b.checked = v; });
  refreshSelAllUnits();
});
document.querySelectorAll('.sel-unit').forEach(b=>{
  b.addEventListener('change', refreshSelAllUnits);
});
refreshSelAllUnits(); // init

// --- Bulk ---
function getSelected(selector){
  return Array.from(document.querySelectorAll(selector+':checked')).map(i=>i.value);
}

async function bulk(action){
  const units = getSelected('.sel-unit');
  const accs  = getSelected('.sel-acc');
  const status = document.getElementById('status');

  if (!units.length || !accs.length) {
    status.textContent = 'Seleziona almeno un’unità e un accessorio.';
    return;
  }

  const btns = ['bulkInclude','bulkExclude','bulkInherit'].map(id => document.getElementById(id));
  btns.forEach(b => b.disabled = true);

  try {
    const fd = new FormData();
    units.forEach(u => fd.append('unit_ids[]', u));
    accs.forEach(a  => fd.append('accessory_ids[]', a));
    fd.append('action', action);

    const r = await fetch(API_BULK, { method:'POST', body: fd });
    const raw = await r.text();
    let data;
    try { data = JSON.parse(raw); }
    catch { throw new Error(`Risposta non-JSON (HTTP ${r.status}): ` + raw.slice(0,200)); }

    if (!data.ok) throw new Error(data.msg || 'Operazione non completata');

    status.textContent = data.msg || 'Operazione eseguita.';
    setTimeout(() => location.reload(), 600);

  } catch (e) {
    alert('Operazione non completata: ' + e.message);
    status.textContent = 'Errore: ' + e.message;
  } finally {
    btns.forEach(b => b.disabled = false);
  }
}

document.getElementById('bulkInclude').onclick = () => bulk('include');
document.getElementById('bulkExclude').onclick = () => bulk('exclude');
document.getElementById('bulkInherit').onclick = () => bulk('inherit');
</script>

<?php page_footer(); ?>

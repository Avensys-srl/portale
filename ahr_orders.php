<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

$ahr = new AHR();
$cliente = trim($_GET['cliente'] ?? '');
$code = trim($_GET['code'] ?? '');
$dal = trim($_GET['dal'] ?? '');
$al  = trim($_GET['al'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
if ($limit < 1) $limit = 1;
if ($limit > 2000) $limit = 2000;

$rows = [];
if ($cliente !== '' || $code !== '' || $dal !== '' || $al !== '') {
  $rows = $ahr->ode($cliente ?: null, $code ?: null, $dal ?: null, $al ?: null);
  $rows = array_slice($rows, 0, $limit);
}

page_header('Ordini clienti (ERP AHR)','ahr');
?>
<div class="toolbar">
  <form method="get" class="row" style="gap:8px;flex-wrap:wrap;">
    <input type="text" name="cliente" value="<?= h($cliente) ?>" placeholder="Cod. cliente" style="width:140px">
    <input type="text" name="code" value="<?= h($code) ?>" placeholder="Codice art (LIKE)" style="width:180px">
    <label>Dal</label>
    <input type="date" name="dal" value="<?= h($dal) ?>">
    <label>Al</label>
    <input type="date" name="al" value="<?= h($al) ?>">
    <label>Limite</label>
    <input type="number" name="limit" value="<?= (int)$limit ?>" min="1" max="2000" style="width:80px">
    <button class="btn" type="submit">Filtra</button>
  </form>
  <p class="muted">Solo lettura da ERP AHR. Origine: ORDCL; default intervallo ultimi 6 mesi se non specificato.</p>
</div>

<?php if(!$rows){ ?>
  <p class="muted">Imposta almeno un filtro per vedere gli ordini.</p>
<?php } else { ?>
  <div style="overflow:auto;">
    <table>
      <tr>
        <th>Codice</th><th>Descrizione</th><th>UM</th><th>Qta</th><th>Prezzo</th><th>Valuta</th><th>Cambio</th><th>Cliente</th><th>Descr. cliente</th><th>Data doc</th><th>Data evasa</th>
      </tr>
      <?php foreach($rows as $r){ ?>
        <tr>
          <td><?= h($r['codice']) ?></td>
          <td><?= h($r['descrizione']) ?></td>
          <td><?= h($r['um']) ?></td>
          <td><?= h($r['quantita']) ?></td>
          <td><?= h($r['prezzo']) ?></td>
          <td><?= h($r['valuta']) ?></td>
          <td><?= h($r['cambio']) ?></td>
          <td><?= h($r['cliente']) ?></td>
          <td><?= h($r['descr_cliente']) ?></td>
          <td><?= h(is_object($r['data_doc']) ? $r['data_doc']->format('Y-m-d') : $r['data_doc']) ?></td>
          <td><?= h(is_object($r['data_evasa']) ? $r['data_evasa']->format('Y-m-d') : $r['data_evasa']) ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php } ?>

<?php page_footer(); ?>

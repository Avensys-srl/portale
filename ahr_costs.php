<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

$ahr = new AHR();
$code = trim($_GET['code'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 300;
if ($limit < 1) $limit = 1;
if ($limit > 1000) $limit = 1000;

$rows = [];
if ($code !== '') {
  $rows = array_slice($ahr->costo_articolo_storico($code), 0, $limit);
}

page_header('Costi articoli (ERP AHR)','ahr');
?>
<div class="toolbar">
  <form method="get" class="row" style="gap:8px;flex-wrap:wrap;">
    <input type="text" name="code" value="<?= h($code) ?>" placeholder="Codice articolo (es. F...)" style="width:200px" required>
    <label>Limite</label>
    <input type="number" name="limit" value="<?= (int)$limit ?>" min="1" max="1000" style="width:80px">
    <button class="btn" type="submit">Cerca</button>
  </form>
  <p class="muted">Solo lettura da ERP AHR. Ordini fornitore (ORDFO) in ordine data documento decrescente.</p>
</div>

<?php if(!$rows){ ?>
  <p class="muted">Inserisci un codice per vedere lo storico costi.</p>
<?php } else { ?>
  <div style="overflow:auto;">
    <table>
      <tr>
        <th>Codice</th><th>Descrizione</th><th>UM</th><th>Quantità</th><th>Prezzo</th><th>Valuta</th><th>Cambio</th><th>Data doc</th><th>Fornitore</th><th>Cod. Forn.</th><th>Evaso</th><th>Mail</th><th>Telefono</th>
      </tr>
      <?php foreach($rows as $r){ ?>
        <tr>
          <td><?= h($r['codice']) ?></td>
          <td><?= h($r['descr']) ?></td>
          <td><?= h($r['um']) ?></td>
          <td><?= h($r['quantita']) ?></td>
          <td><?= h($r['prezzo']) ?></td>
          <td><?= h($r['valuta']) ?></td>
          <td><?= h($r['cambio']) ?></td>
          <td><?= h(is_object($r['data']) ? $r['data']->format('Y-m-d') : $r['data']) ?></td>
          <td><?= h($r['fornitore']) ?></td>
          <td><?= h($r['codice_f']) ?></td>
          <td><?= h($r['evaso']) ?></td>
          <td><?= h($r['mail']) ?></td>
          <td><?= h($r['telefono']) ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php } ?>

<?php page_footer(); ?>

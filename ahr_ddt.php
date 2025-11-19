<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

$ahr = new AHR();
$docnum = trim($_GET['docnum'] ?? '');
$year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');

$rows = [];
if ($docnum !== '') {
  $rows = $ahr->ddt($docnum, $year);
}

page_header('DDT vendita (ERP AHR)','ahr');
?>
<div class="toolbar">
  <form method="get" class="row" style="gap:8px;flex-wrap:wrap;">
    <input type="text" name="docnum" value="<?= h($docnum) ?>" placeholder="Numero DDT" style="width:140px" required>
    <label>Anno</label>
    <input type="number" name="year" value="<?= (int)$year ?>" min="2000" max="<?= date('Y')+1 ?>" style="width:90px">
    <button class="btn" type="submit">Cerca</button>
  </form>
  <p class="muted">Solo lettura da ERP AHR. DDT vendita (DDTVE).</p>
</div>

<?php if (!$rows){ ?>
  <p class="muted">Inserisci numero DDT per vedere i movimenti.</p>
<?php } else { ?>
  <div style="overflow:auto;">
    <table>
      <tr><th>DDT</th><th>Ordine</th><th>Item</th><th>Cod. cliente</th><th>Descrizione</th><th>Quantità</th></tr>
      <?php foreach($rows as $r){ ?>
        <tr>
          <td><?= h($r['DDT Number']) ?></td>
          <td><?= h($r['Order Number']) ?></td>
          <td><?= h($r['Item Code']) ?></td>
          <td><?= h($r['Customer Code']) ?></td>
          <td><?= h($r['Description']) ?></td>
          <td><?= h($r['Quantity']) ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php } ?>

<?php page_footer(); ?>

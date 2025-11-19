<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

$filter = trim($_GET['q'] ?? '');
$fam = trim($_GET['fam'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 200;
if ($limit < 1) $limit = 1;
if ($limit > 1000) $limit = 1000;

$ahr = new AHR();
$articles = [];

if ($filter !== '' || $fam !== '') {
  $articles = $ahr->articoli($filter !== '' ? $filter : null, $fam !== '' ? $fam : null);
  $articles = array_slice($articles, 0, $limit);
}

page_header('Articoli (ERP AHR)','ahr');
?>
<div class="toolbar">
  <form method="get" class="row" style="gap:8px; flex-wrap:wrap;">
    <input type="text" name="q" value="<?= h($filter) ?>" placeholder="Codice (LIKE, es. F%)" style="width:180px">
    <input type="text" name="fam" value="<?= h($fam) ?>" placeholder="Famiglia (LIKE, es. HVAC%)" style="width:180px">
    <label>Limite</label>
    <input type="number" name="limit" value="<?= (int)$limit ?>" min="1" max="1000" style="width:80px">
    <button class="btn" type="submit">Cerca</button>
  </form>
  <p class="muted">Ricerca on-demand (read-only). Usa wildcard SQL (es. F%% per include).</p>
</div>

<?php if(!$articles){ ?>
  <p class="muted">Inserisci un filtro per mostrare gli articoli.</p>
<?php } else { ?>
  <div style="overflow:auto;">
    <table>
      <tr><th>Codice</th><th>Descrizione</th><th>Descrizione 2</th><th>UM</th><th>Famiglia</th></tr>
      <?php foreach($articles as $a){ ?>
        <tr>
          <td><?= h($a['codice']) ?></td>
          <td><?= h($a['descr']) ?></td>
          <td><?= h($a['descr2']) ?></td>
          <td><?= h($a['um']) ?></td>
          <td><?= h($a['fam']) ?></td>
        </tr>
      <?php } ?>
    </table>
  </div>
<?php } ?>

<?php page_footer(); ?>

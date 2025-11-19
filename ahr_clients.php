<?php
require 'functions.php';
require 'layout.php';
require 'db.php';
require 'AHR_class.php';

$ahr = new AHR();
$code = trim($_GET['code'] ?? '');
$descr = trim($_GET['descr'] ?? '');
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
if ($limit < 1) $limit = 1;
if ($limit > 2000) $limit = 2000;

$clients = $ahr->clienti($code ?: null, $descr ?: null, $limit);

page_header('Clienti (ERP AHR)','ahr');
?>
<div class="toolbar">
  <form method="get" class="row" style="gap:8px;flex-wrap:wrap;">
    <input type="text" name="code" value="<?= h($code) ?>" placeholder="Codice (LIKE, es. 0%)" style="width:160px">
    <input type="text" name="descr" value="<?= h($descr) ?>" placeholder="Descrizione (LIKE, es. ACME%)" style="width:200px">
    <label>Limite</label>
    <input type="number" name="limit" value="<?= (int)$limit ?>" min="1" max="2000" style="width:80px">
    <button class="btn" type="submit">Filtra</button>
  </form>
  <p class="muted">Solo lettura da ERP AHR.</p>
</div>
<div style="overflow:auto;">
  <table>
    <tr><th>Codice</th><th>Descrizione</th></tr>
    <?php foreach($clients as $c){ ?>
      <tr>
        <td><?= h($c['codice']) ?></td>
        <td><?= h($c['descr']) ?></td>
      </tr>
    <?php } ?>
  </table>
</div>
<?php page_footer(); ?>

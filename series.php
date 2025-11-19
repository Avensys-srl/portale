<?php
require 'db.php';
require 'functions.php';
require 'layout.php';

// CRUD serie
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['series_action'])) {
  $code = trim($_POST['code'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  if ($code !== '' && $name !== '') {
    if ($_POST['series_action']==='add') {
      db_exec($conn, "INSERT INTO dbo.CLSeries(Code,Name,Description) VALUES(?,?,?)", [$code, $name, $desc]);
    } elseif ($_POST['series_action']==='upd') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id) {
        db_exec($conn, "UPDATE dbo.CLSeries SET Code=?, Name=?, Description=? WHERE Id=?", [$code, $name, $desc, $id]);
      }
    }
  }
}

$rows = db_all($conn, "SELECT Id, Code, Name, Description FROM dbo.CLSeries ORDER BY Id DESC");

page_header('Serie CLDC','cldc');
?>
<section class="toolbar">
  <form method="post" class="row" style="gap:8px;">
    <input type="hidden" name="series_action" value="add">
    <input type="text" name="code" placeholder="Code" required>
    <input type="text" name="name" placeholder="Name" required>
    <input type="text" name="description" placeholder="Description">
    <button class="btn primary" type="submit">Aggiungi</button>
  </form>
</section>

<div style="overflow:auto;">
  <table>
    <tr><th>Id</th><th>Code</th><th>Name</th><th>Description</th><th>Azioni</th></tr>
    <?php foreach($rows as $r){ ?>
      <tr>
        <td><?= (int)$r['Id'] ?></td>
        <td><?= h($r['Code']) ?></td>
        <td><?= h($r['Name']) ?></td>
        <td><?= h($r['Description']) ?></td>
        <td>
          <form method="post" class="row" style="gap:6px; flex-wrap:wrap;">
            <input type="hidden" name="series_action" value="upd">
            <input type="hidden" name="id" value="<?= (int)$r['Id'] ?>">
            <input type="text" name="code" value="<?= h($r['Code']) ?>" style="width:110px">
            <input type="text" name="name" value="<?= h($r['Name']) ?>" style="width:180px">
            <input type="text" name="description" value="<?= h($r['Description']) ?>" style="width:220px">
            <button class="btn" type="submit">Salva</button>
          </form>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>

<?php page_footer(); ?>

<?php
require 'db.php';
require 'functions.php';
require 'layout.php';

// CRUD layout (IdEnum = 4 fisso)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['layout_action'])) {
  $intCode = trim($_POST['intcode'] ?? '');
  $textCode = trim($_POST['textcode'] ?? '');
  $name = trim($_POST['name'] ?? '');
  $desc = trim($_POST['description'] ?? '');
  $userData1 = trim($_POST['userdata1'] ?? '');
  if ($intCode !== '' && $textCode !== '') {
    if ($_POST['layout_action']==='add') {
      db_exec($conn, "INSERT INTO dbo.CLEnumItems(IdEnum,IntCode,TextCode,Name,Description,UserData1) VALUES(4,?,?,?,?,?)",
        [$intCode, $textCode, $name, $desc, $userData1]);
    } elseif ($_POST['layout_action']==='upd') {
      $id = (int)($_POST['id'] ?? 0);
      if ($id) {
        db_exec($conn, "UPDATE dbo.CLEnumItems SET IntCode=?, TextCode=?, Name=?, Description=?, UserData1=? WHERE IdEnum=4 AND Id=?",
          [$intCode, $textCode, $name, $desc, $userData1, $id]);
      }
    }
  }
}

$rows = db_all($conn, "SELECT Id, IntCode, TextCode, Name, Description, UserData1 FROM dbo.CLEnumItems WHERE IdEnum=4 ORDER BY Id DESC");

page_header('Layout (IdEnum=4)','cldc');
?>
<section class="toolbar">
  <form method="post" class="row" style="gap:8px;">
    <input type="hidden" name="layout_action" value="add">
    <input type="text" name="intcode" placeholder="IntCode" required>
    <input type="text" name="textcode" placeholder="TextCode" required>
    <input type="text" name="name" placeholder="Name">
    <input type="text" name="description" placeholder="Description">
    <input type="text" name="userdata1" placeholder="UserData1">
    <button class="btn primary" type="submit">Aggiungi</button>
  </form>
</section>

<div style="overflow:auto;">
  <table>
    <tr><th>Id</th><th>IntCode</th><th>TextCode</th><th>Name</th><th>Description</th><th>UserData1</th><th>Azioni</th></tr>
    <?php foreach($rows as $r){ ?>
      <tr>
        <td><?= (int)$r['Id'] ?></td>
        <td><?= h($r['IntCode']) ?></td>
        <td><?= h($r['TextCode']) ?></td>
        <td><?= h($r['Name']) ?></td>
        <td><?= h($r['Description']) ?></td>
        <td><?= h($r['UserData1']) ?></td>
        <td>
          <form method="post" class="row" style="gap:6px; flex-wrap:wrap;">
            <input type="hidden" name="layout_action" value="upd">
            <input type="hidden" name="id" value="<?= (int)$r['Id'] ?>">
            <input type="text" name="intcode" value="<?= h($r['IntCode']) ?>" style="width:110px">
            <input type="text" name="textcode" value="<?= h($r['TextCode']) ?>" style="width:110px">
            <input type="text" name="name" value="<?= h($r['Name']) ?>" style="width:120px">
            <input type="text" name="description" value="<?= h($r['Description']) ?>" style="width:180px">
            <input type="text" name="userdata1" value="<?= h($r['UserData1']) ?>" style="width:120px">
            <button class="btn" type="submit">Salva</button>
          </form>
        </td>
      </tr>
    <?php } ?>
  </table>
</div>

<?php page_footer(); ?>

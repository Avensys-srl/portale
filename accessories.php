<?php
require 'db.php'; require 'functions.php'; require 'layout.php';
page_header('Gestione Accessori & Famiglie','cldc');

// insert/update famiglia
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['fam_action'])){
  if($_POST['fam_action']==='add'){
    db_exec($conn, "INSERT INTO dbo.AccessoryFamily(Code,Name) VALUES(?,?)",
      [$_POST['fam_code'], $_POST['fam_name']]);
  } elseif($_POST['fam_action']==='upd'){
    db_exec($conn, "UPDATE dbo.AccessoryFamily SET Code=?, Name=? WHERE Id=?",
      [$_POST['fam_code'], $_POST['fam_name'], (int)$_POST['fam_id']]);
  }
}

// insert/update accessorio
if($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['acc_action'])){
  if($_POST['acc_action']==='add'){
    db_exec($conn, "INSERT INTO dbo.Accessory(Code,NameKey,FamilyId,DefaultScope,Active) VALUES(?,?,?,?,?)",
      [$_POST['acc_code'], $_POST['acc_namekey'], (int)$_POST['acc_family'], $_POST['acc_scope'], (int)$_POST['acc_active']]);
  } elseif($_POST['acc_action']==='upd'){
    db_exec($conn, "UPDATE dbo.Accessory SET Code=?, NameKey=?, FamilyId=?, DefaultScope=?, Active=? WHERE Id=?",
      [$_POST['acc_code'], $_POST['acc_namekey'], (int)$_POST['acc_family'], $_POST['acc_scope'], (int)$_POST['acc_active'], (int)$_POST['acc_id']]);
  }
}

$fams = db_all($conn, "SELECT Id,Code,Name FROM dbo.AccessoryFamily ORDER BY Name");
$accs = db_all($conn, "SELECT a.Id,a.Code,a.NameKey,a.DefaultScope,a.Active,f.Name AS FamilyName,f.Id AS FamilyId
                       FROM dbo.Accessory a JOIN dbo.AccessoryFamily f ON f.Id=a.FamilyId
                       ORDER BY f.Name,a.Code");
?>
<div class="grid">
  <section>
    <h3>Famiglie</h3>
    <form method="post" class="row">
      <input type="hidden" name="fam_action" value="add">
      <input type="text" name="fam_code" placeholder="Code" required>
      <input type="text" name="fam_name" placeholder="Name" required>
      <button class="btn primary" type="submit">Aggiungi</button>
    </form>
    <table>
      <tr><th>Id</th><th>Code</th><th>Name</th><th>Azioni</th></tr>
      <?php foreach($fams as $f): ?>
        <tr>
          <td><?= (int)$f['Id'] ?></td>
          <td><?= h($f['Code']) ?></td>
          <td><?= h($f['Name']) ?></td>
          <td>
            <form method="post" class="row" style="gap:6px">
              <input type="hidden" name="fam_action" value="upd">
              <input type="hidden" name="fam_id" value="<?= (int)$f['Id'] ?>">
              <input type="text" name="fam_code" value="<?= h($f['Code']) ?>">
              <input type="text" name="fam_name" value="<?= h($f['Name']) ?>">
              <button class="btn" type="submit">Salva</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>

  <section>
    <h3>Accessori</h3>
    <form method="post" class="row">
      <input type="hidden" name="acc_action" value="add">
      <input type="text" name="acc_code" placeholder="Code" required>
      <input type="text" name="acc_namekey" placeholder="NameKey (chiave JSON)" required>
      <select name="acc_family" required>
        <option value="">Famiglia…</option>
        <?php foreach($fams as $f) echo '<option value="'.(int)$f['Id'].'">'.h($f['Name']).'</option>'; ?>
      </select>
      <select name="acc_scope">
        <option value="all">default: all</option>
        <option value="none">default: none</option>
      </select>
      <select name="acc_active">
        <option value="1">Active</option>
        <option value="0">Inactive</option>
      </select>
      <button class="btn primary" type="submit">Aggiungi</button>
    </form>

    <table>
      <tr><th>Id</th><th>Code</th><th>NameKey</th><th>Famiglia</th><th>Scope</th><th>Active</th><th>Azioni</th></tr>
      <?php foreach($accs as $a): ?>
        <tr>
          <td><?= (int)$a['Id'] ?></td>
          <td><?= h($a['Code']) ?></td>
          <td><?= h($a['NameKey']) ?></td>
          <td><?= h($a['FamilyName']) ?></td>
          <td><span class="tag"><?= h($a['DefaultScope']) ?></span></td>
          <td><?= $a['Active'] ? '✓' : '—' ?></td>
          <td>
            <form method="post" class="row" style="gap:6px">
              <input type="hidden" name="acc_action" value="upd">
              <input type="hidden" name="acc_id" value="<?= (int)$a['Id'] ?>">
              <input type="text" name="acc_code" value="<?= h($a['Code']) ?>" style="width:110px">
              <input type="text" name="acc_namekey" value="<?= h($a['NameKey']) ?>" style="width:170px">
              <select name="acc_family">
                <?php foreach($fams as $f){
                  $sel = ($f['Id']==$a['FamilyId'])?' selected':'';
                  echo '<option value="'.(int)$f['Id'].'"'.$sel.'>'.h($f['Name']).'</option>';
                }?>
              </select>
              <select name="acc_scope">
                <option value="none" <?= $a['DefaultScope']==='none'?'selected':'' ?>>none</option>
                <option value="all"  <?= $a['DefaultScope']==='all'?'selected':'' ?>>all</option>
              </select>
              <select name="acc_active">
                <option value="1" <?= $a['Active']?'selected':'' ?>>Active</option>
                <option value="0" <?= !$a['Active']?'selected':'' ?>>Inactive</option>
              </select>
              <button class="btn" type="submit">Salva</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </table>
  </section>
</div>
<?php page_footer(); ?>

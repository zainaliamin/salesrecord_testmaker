<?php
class AdminBoardController {
  public function index(){
    require_role('admin'); global $pdo;

    $boards = $pdo->query("SELECT id, name FROM exam_boards ORDER BY name")->fetchAll();

    // Optional: get usage counts for safety info
    $usage = $pdo->query("SELECT exam_board AS name, COUNT(*) AS cnt
                          FROM sales
                          WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
                            AND created_at <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
                          GROUP BY exam_board")->fetchAll();
    $usageMap = [];
    foreach($usage as $u){ $usageMap[$u['name']] = (int)$u['cnt']; }

    require __DIR__.'/../../views/admin/boards/index.php';
  }

  public function create(){
    require_role('admin');
    require __DIR__.'/../../views/admin/boards/create.php';
  }

  public function store(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $name = trim($_POST['name'] ?? '');
    if ($name === '') exit('Board name is required');
    if (mb_strlen($name) > 64) exit('Board name too long');

    // insert (unique constraint prevents dupes)
    $st = $pdo->prepare("INSERT INTO exam_boards(name) VALUES(?)");
    try {
      $st->execute([$name]);
    } catch (PDOException $e) {
      exit('This board already exists');
    }

    redirect('admin/boards');
  }

  public function delete(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Safety: block delete if used in any sale (you can loosen this if you want)
    $st = $pdo->prepare("SELECT name FROM exam_boards WHERE id=? LIMIT 1");
    $st->execute([$id]);
    $row = $st->fetch();
    if (!$row) exit('Not found');

    $name = $row['name'];

    $st = $pdo->prepare("SELECT COUNT(*) FROM sales WHERE exam_board=?");
    $st->execute([$name]);
    $used = (int)$st->fetchColumn();

    if ($used > 0) {
      exit('Cannot delete: this board is used by existing sales');
    }

    $pdo->prepare("DELETE FROM exam_boards WHERE id=? LIMIT 1")->execute([$id]);
    redirect('admin/boards');
  }

  public function edit(){
  require_role('admin'); global $pdo;

  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) exit('Invalid ID');

  $st = $pdo->prepare("SELECT id,name FROM exam_boards WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $board = $st->fetch();
  if (!$board) exit('Board not found');

  require __DIR__.'/../../views/admin/boards/edit.php';
 }

public function update(){
  require_role('admin'); global $pdo;

  if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

  $id      = (int)($_POST['id'] ?? 0);
  $newName = trim($_POST['name'] ?? '');

  if ($id <= 0) exit('Invalid ID');
  if ($newName === '') exit('Board name is required');
  if (mb_strlen($newName) > 64) exit('Board name too long');

  // fetch old name
  $st = $pdo->prepare("SELECT name FROM exam_boards WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $row = $st->fetch();
  if (!$row) exit('Board not found');
  $oldName = $row['name'];

  // if name unchanged, just return
  if ($oldName === $newName) redirect('admin/boards');

  // ensure uniqueness
  $st = $pdo->prepare("SELECT 1 FROM exam_boards WHERE name=? LIMIT 1");
  $st->execute([$newName]);
  if ($st->fetch()) exit('Another board with this name already exists');

  // update in a transaction: exam_boards then sales
  $pdo->beginTransaction();
  try {
    // 1) update lookup
    $st = $pdo->prepare("UPDATE exam_boards SET name=? WHERE id=?");
    $st->execute([$newName, $id]);

    // 2) cascade rename to sales (all rows, any month)
    $st = $pdo->prepare("UPDATE sales SET exam_board=? WHERE exam_board=?");
    $st->execute([$newName, $oldName]);

    $pdo->commit();
  } catch (Exception $e) {
    $pdo->rollBack();
    exit('Rename failed: '.$e->getMessage());
  }

  redirect('admin/boards');
 }

}

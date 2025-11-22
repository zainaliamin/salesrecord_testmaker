<?php
class AdminExpenseController {
  public function index(){
    require_role('admin'); global $pdo;

    // Current month window
    $sql = "SELECT id,name,amount,created_at
            FROM expenses
            WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            ORDER BY id DESC";
    $rows = $pdo->query($sql)->fetchAll();

    $total = 0;
    foreach ($rows as $r) $total += (int)$r['amount'];

    require __DIR__.'/../../views/admin/expenses/index.php';
  }

  public function store(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $name   = trim($_POST['name'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);

    if ($name === '') exit('Expense name is required');
    if ($amount <= 0) exit('Amount must be greater than 0');

    $st = $pdo->prepare("INSERT INTO expenses(name,amount,created_at) VALUES(?, ?, NOW())");
    $st->execute([$name, $amount]);

    redirect('admin/expenses');
  }
    public function edit(){
    require_role('admin'); global $pdo;

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Restrict to current month (consistent with index list)
    $sql = "SELECT id,name,amount,created_at
            FROM expenses
            WHERE id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);
    $expense = $st->fetch();
    if (!$expense) { http_response_code(404); exit('Expense not found (outside this month)'); }

    require __DIR__.'/../../views/admin/expenses/edit.php';
  }

  public function update(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $id     = (int)($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');
    $amount = (int)($_POST['amount'] ?? 0);

    if ($id <= 0) exit('Invalid ID');
    if ($name === '') exit('Expense name is required');
    if ($amount <= 0) exit('Amount must be greater than 0');

    // Only allow updates for current-month rows
    $sql = "UPDATE expenses
            SET name = ?, amount = ?
            WHERE id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$name, $amount, $id]);

    redirect('admin/expenses');
  }

  public function delete(){
    require_role('admin'); global $pdo;
    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Only allow deletes for current-month rows
    $sql = "DELETE FROM expenses
            WHERE id = ?
              AND created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01')
              AND created_at <  DATE_FORMAT(DATE_ADD(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$id]);

    redirect('admin/expenses');
  }

}

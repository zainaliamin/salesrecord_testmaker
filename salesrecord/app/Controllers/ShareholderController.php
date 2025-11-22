<?php
class ShareholderController {

  // GET /admin/shareholder
  public function index(){
    require_role('admin');
    global $pdo;

    $snapshot = $this->reserveSnapshot();

    // latest spends (you can paginate later if you like)
    $spends = $pdo->query("
      SELECT s.id, s.amount, s.note, s.created_at, u.name AS by_name
      FROM shareholder_spends s
      LEFT JOIN users u ON u.id = s.created_by_user_id
      ORDER BY s.id DESC
      LIMIT 200
    ")->fetchAll();

    $editSpend = null;
    $editId = isset($_GET['edit_id']) ? (int)$_GET['edit_id'] : 0;
    if ($editId > 0) {
      $st = $pdo->prepare("SELECT id, amount, note FROM shareholder_spends WHERE id = ? LIMIT 1");
      $st->execute([$editId]);
      $editSpend = $st->fetch() ?: null;
    }

    $kpis = [
      'profit_all'    => $snapshot['profit_all'],
      'reserve_15'    => $snapshot['reserve_15'],
      'spent_total'   => $snapshot['spent_total'],
      'available'     => $snapshot['available'],
    ];

    require __DIR__.'/../../views/admin/shareholder.php';
  }

  // POST /admin/shareholder/spend
  public function store(){
    require_role('admin');
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

    $amount = (int)($_POST['amount'] ?? 0);
    $note   = trim((string)($_POST['note'] ?? ''));

    if ($amount <= 0)          { http_response_code(400); exit('Amount must be > 0'); }
    if ($note === '')          { http_response_code(400); exit('Note is required'); }
    if (mb_strlen($note) > 200){ http_response_code(400); exit('Note too long (max 200)'); }

    $snapshot = $this->reserveSnapshot();
    $available = $snapshot['available'];

    if ($amount > $available) {
      http_response_code(400);
      exit('Not enough shareholder reserve available.');
    }

    // Insert spend
    $ins = $pdo->prepare("
      INSERT INTO shareholder_spends(amount, note, created_by_user_id, created_at)
      VALUES(?, ?, ?, NOW())
    ");
    $ins->execute([$amount, $note, (int)user()['id']]);

    // PRG pattern
    redirect('admin/shareholder');
  }

  public function update(){
    require_role('admin');
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

    $id     = (int)($_POST['id'] ?? 0);
    $amount = (int)($_POST['amount'] ?? 0);
    $note   = trim((string)($_POST['note'] ?? ''));

    if ($id <= 0)              { http_response_code(400); exit('Invalid record'); }
    if ($amount <= 0)          { http_response_code(400); exit('Amount must be > 0'); }
    if ($note === '')          { http_response_code(400); exit('Note is required'); }
    if (mb_strlen($note) > 200){ http_response_code(400); exit('Note too long (max 200)'); }

    $st = $pdo->prepare("SELECT id, amount FROM shareholder_spends WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    $current = $st->fetch();
    if (!$current) { http_response_code(404); exit('Spend not found'); }

    $snapshot   = $this->reserveSnapshot();
    $maxAllowed = $snapshot['available'] + (int)$current['amount'];
    if ($amount > $maxAllowed) {
      http_response_code(400);
      exit('Not enough shareholder reserve available for this edit.');
    }

    $upd = $pdo->prepare("UPDATE shareholder_spends SET amount = ?, note = ? WHERE id = ? LIMIT 1");
    $upd->execute([$amount, $note, $id]);

    redirect('admin/shareholder');
  }

  public function delete(){
    require_role('admin');
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) { http_response_code(400); exit('Bad CSRF'); }

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { http_response_code(400); exit('Invalid record'); }

    $st = $pdo->prepare("SELECT id FROM shareholder_spends WHERE id = ? LIMIT 1");
    $st->execute([$id]);
    if (!$st->fetch()) { http_response_code(404); exit('Spend not found'); }

    $pdo->prepare("DELETE FROM shareholder_spends WHERE id = ? LIMIT 1")->execute([$id]);

    redirect('admin/shareholder');
  }

  private function reserveSnapshot(): array {
    global $pdo;
    $grossAll = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM sale_payments")->fetchColumn();
    $commAll  = (int)$pdo->query("SELECT COALESCE(SUM(commission_amount),0) FROM sales WHERE status='approved'")->fetchColumn();
    $expAll   = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM expenses")->fetchColumn();
    $profitAll = $grossAll - $commAll - $expAll;
    $reserveGross = (int)round($profitAll * 0.15);
    $spentTotal   = (int)$pdo->query("SELECT COALESCE(SUM(amount),0) FROM shareholder_spends")->fetchColumn();
    $available    = max(0, $reserveGross - $spentTotal);

    return [
      'gross_all'   => $grossAll,
      'comm_all'    => $commAll,
      'exp_all'     => $expAll,
      'profit_all'  => $profitAll,
      'reserve_15'  => $reserveGross,
      'spent_total' => $spentTotal,
      'available'   => $available,
    ];
  }
}

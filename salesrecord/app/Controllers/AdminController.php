<?php
class AdminController {

  public function index(){
  require_role('admin');
  global $pdo;

  // Get window from query (?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD),
  // fallback = this month. Label is friendly (“This Month” or from→to).
  [$from, $to] = window_from_query();
  $label = window_label($from, $to);

  // Use half-open range [from 00:00:00, to 00:00:00)
  $wStart = $from.' 00:00:00';
  $wEnd   = $to.' 00:00:00';

  $status = $_GET['status'] ?? null; // pending|approved|rejected|null

  // --- main list (join users) — bound to selected window
  if ($status) {
    $sql = "SELECT s.*, u.name AS agent_user_name, u.email AS agent_user_email
            FROM sales s
            JOIN users u ON u.id = s.agent_user_id
            WHERE s.created_at >= ? AND s.created_at < ? AND s.status = ?
            ORDER BY s.id ASC";
    $st  = $pdo->prepare($sql);
    $st->execute([$wStart, $wEnd, $status]);
  } else {
    $sql = "SELECT s.*, u.name AS agent_user_name, u.email AS agent_user_email
            FROM sales s
            JOIN users u ON u.id = s.agent_user_id
            WHERE s.created_at >= ? AND s.created_at < ?
            ORDER BY s.id DESC";
    $st  = $pdo->prepare($sql);
    $st->execute([$wStart, $wEnd]);
  }
    $sales = $st->fetchAll();

    $dueParams = [$wStart, $wEnd];
    $dueSql = "
      SELECT dp.*, s.school_name AS sale_school, s.phone AS sale_phone, s.amount_due AS current_amount_due,
             u.name AS agent_name, r.name AS reviewer_name
      FROM sale_due_payments dp
      LEFT JOIN sales s ON s.id = dp.sale_id
      LEFT JOIN users u ON u.id = dp.agent_user_id
      LEFT JOIN users r ON r.id = dp.reviewed_by_user_id
      WHERE dp.created_at >= ? AND dp.created_at < ?
    ";
    if ($status && in_array($status, ['pending','approved','rejected'], true)) {
      $dueSql .= " AND dp.status = ?";
      $dueParams[] = $status;
    }
    $dueSql .= " ORDER BY (dp.status='pending') DESC, dp.id DESC";
    $stDue = $pdo->prepare($dueSql);
    $stDue->execute($dueParams);
    $duePayments = $stDue->fetchAll();

  // --- KPI calculations — all bound to window
  // Gross from ledger (sale_payments.paid_at)
  $st = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM sale_payments
    WHERE paid_at >= ? AND paid_at < ?
  ");
  $st->execute([$wStart, $wEnd]);
  $income = (int)$st->fetchColumn();

  // Commissions from approved sales created in window
  $st = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
    FROM sales s
    WHERE s.status='approved'
      AND s.created_at >= ? AND s.created_at < ?
  ");
  $st->execute([$wStart, $wEnd]);
  $commissions = (int)$st->fetchColumn();

  // Expenses in window
  $st = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM expenses e
    WHERE e.created_at >= ? AND e.created_at < ?
  ");
  $st->execute([$wStart, $wEnd]);
  $expenses = (int)$st->fetchColumn();

  $netAfterCommissions = $income - $commissions;

  $kpis = [
    'gross'           => $income,
    'commissions'     => $commissions,
    'net_after_comm'  => $netAfterCommissions,
    'expenses'        => $expenses,
    'profit'          => $netAfterCommissions - $expenses,
    'label'           => $label,      // e.g., “This Month” or “2025-09-01 → 2025-09-30”
    'from'            => $from,       // pass to view for form defaults
    'to'              => $to,
  ];

  // --- Exam Board summary: dashboard only (no status), APPROVED only
  $boardSummary = null;
  $boardTotals  = null;
  if (!$status) {
    $allBoards = $pdo->query("SELECT name FROM exam_boards ORDER BY name")
                     ->fetchAll(PDO::FETCH_COLUMN);

    $bSql = "SELECT s.exam_board,
                    COUNT(*)                        AS cnt,
                    COALESCE(SUM(s.amount_paid), 0) AS paid_sum,
                    COALESCE(SUM(s.amount_due),  0) AS due_sum
             FROM sales s
             WHERE s.created_at >= ? AND s.created_at < ? AND s.status = 'approved'
             GROUP BY s.exam_board
             ORDER BY s.exam_board";
    $bst = $pdo->prepare($bSql);
    $bst->execute([$wStart, $wEnd]);
    $rows = $bst->fetchAll();

    $boardSummary = [];
    foreach ($allBoards as $b) {
      $boardSummary[$b] = ['cnt' => 0, 'paid' => 0, 'due' => 0];
    }
    foreach ($rows as $r) {
      $name = $r['exam_board'];
      $boardSummary[$name] = [
        'cnt'  => (int)$r['cnt'],
        'paid' => (int)$r['paid_sum'],
        'due'  => (int)$r['due_sum'],
      ];
    }

    $boardTotals = ['cnt' => 0, 'paid' => 0, 'due' => 0];
    foreach ($boardSummary as $agg) {
      $boardTotals['cnt']  += $agg['cnt'];
      $boardTotals['paid'] += $agg['paid'];
      $boardTotals['due']  += $agg['due'];
    }
  }

  // --- Per-agent commission summary (approved only) — dashboard only
  // --- Per-agent commission summary (window; approved only) — dashboard only
$agentCommissions = [];
if (!$status) {
  $st = $pdo->prepare("
    SELECT
      u.id,
      u.name,
      u.email,

      /* Sum of commissions within window for approved sales */
      (
        SELECT COALESCE(SUM(s2.commission_amount), 0)
        FROM sales s2
        WHERE s2.agent_user_id = u.id
          AND s2.status = 'approved'
          AND s2.created_at >= ?
          AND s2.created_at <  ?
      ) AS commission_sum,

      /* Count of approved sales within window */
      (
        SELECT COUNT(*)
        FROM sales s3
        WHERE s3.agent_user_id = u.id
          AND s3.status = 'approved'
          AND s3.created_at >= ?
          AND s3.created_at <  ?
      ) AS sale_count

    FROM users u
    WHERE u.role = 'agent'
    ORDER BY commission_sum DESC, sale_count DESC, u.name ASC
  ");
  // IMPORTANT: bind the actual window vars from this method
  $st->execute([$wStart, $wEnd, $wStart, $wEnd]);
  $agentCommissions = $st->fetchAll();
}


  require __DIR__.'/../../views/admin/list.php';
}


  public function show(){
  require_role('admin');
  global $pdo;

  $id = (int)($_GET['id'] ?? 0);
  if ($id <= 0) { http_response_code(400); exit('Invalid ID'); }

  // Admins can VIEW any record by ID (no month restriction).
  $sql = "SELECT s.*, u.name AS agent_user_name, u.email AS agent_user_email
          FROM sales s
          JOIN users u ON u.id = s.agent_user_id
          WHERE s.id = ?
          LIMIT 1";
  $st = $pdo->prepare($sql);
  $st->execute([$id]);
  $sale = $st->fetch();

  if (!$sale) { http_response_code(404); exit('Not Found'); }

  require __DIR__.'/../../views/admin/show.php';
}


  public function approve(){
    require_role('admin'); 
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Approve only if currently pending
    $upd = $pdo->prepare("
      UPDATE sales
      SET status='approved', approved_by=?, approved_at=NOW()
      WHERE id=? AND status='pending'
    ");
    $upd->execute([ user()['id'], $id ]);

    // If approved and there was upfront payment, log it in ledger (with receipt)
    if ($upd->rowCount() > 0) {
      $st = $pdo->prepare("
        SELECT school_name, phone, amount_paid, payment_method, agent_user_id, customer_type, receipt_image_path
        FROM sales
        WHERE id=? LIMIT 1
      ");
      $st->execute([$id]);
      $s = $st->fetch();

      if ($s && (int)$s['amount_paid'] > 0) {
        $method = $s['payment_method'];
        if (!in_array($method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) {
          $method = 'cash';
        }

        // Decide source tag for the upfront payment
        $src = ($s['customer_type'] === 'old') ? 'renewal' : 'new';

        // Carry over the sale receipt (if any) into the ledger row
        $receiptPath = $s['receipt_image_path'] ?: null;

        $ins = $pdo->prepare("
          INSERT INTO sale_payments
            (sale_id, school_name, phone, amount, method, source, agent_user_id, receipt_path, paid_at, created_at)
          VALUES
            (?,       ?,           ?,     ?,      ?,      ?,      ?,            ?,            NOW(),  NOW())
        ");
        $ins->execute([
          $id,
          $s['school_name'],
          $s['phone'],
          (int)$s['amount_paid'],
          $method,
          $src,
          (int)$s['agent_user_id'],
          $receiptPath
        ]);
      }
    }

    redirect('admin/sales?status=pending');
  }

  public function reject(){
    require_role('admin');
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $id   = (int)($_POST['id'] ?? 0);
    $note = clean($_POST['note'] ?? '');
    if ($id <= 0) exit('Invalid ID');
    if ($note === '') exit('Rejection note required');

    $pdo->beginTransaction();

    // 1) Set sale to rejected (only if currently pending)
    $pdo->prepare("UPDATE sales SET status='rejected' WHERE id=? AND status='pending'")->execute([$id]);

    // 2) Remove ALL ledger rows for this sale_id (so gross/net/profit no longer count it)
    $pdo->prepare("DELETE FROM sale_payments WHERE sale_id = ?")->execute([$id]);

    // 3) Log the action
    $pdo->prepare("
      INSERT INTO approval_logs(sale_id,action,by_user_id,note,created_at)
      VALUES(?,?,?,?,NOW())
    ")->execute([$id,'rejected',user()['id'],$note]);

    $pdo->commit();

    redirect('admin/sales?status=pending');
  }

  public function edit(){
    require_role('admin');
    global $pdo;

    $w = current_month_window();

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Only allow editing current-month records
    $sql = "SELECT s.*, u.name AS agent_user_name, u.email AS agent_user_email
            FROM sales s
            JOIN users u ON u.id = s.agent_user_id
            WHERE s.id = ?
              AND s.created_at >= ? AND s.created_at < ?
            LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute([$id, $w['start'], $w['end']]);
    $sale = $st->fetch();
    if (!$sale) { http_response_code(404); exit('Not Found (outside this month)'); }

    $boards = $pdo->query("SELECT name FROM exam_boards ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    require __DIR__.'/../../views/admin/edit.php';
  }

  public function update(){
    require_role('admin');
    global $pdo, $config;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $w = current_month_window();

    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    // Fetch only if in current month
    $st = $pdo->prepare("SELECT * FROM sales
                         WHERE id = ?
                           AND created_at >= ? AND created_at < ?
                         LIMIT 1");
    $st->execute([$id, $w['start'], $w['end']]);
    $current = $st->fetch();
    if (!$current) { http_response_code(404); exit('Not Found (outside this month)'); }

    // Collect inputs
    $fields = [
      'full_name'           => clean($_POST['full_name'] ?? ''),
      'phone'               => preg_replace('/\D+/', '', $_POST['phone'] ?? ''),
      'city'                => clean($_POST['city'] ?? ''),
      'school_name'         => clean($_POST['school_name'] ?? ''),
      'module_name'         => clean($_POST['module_name'] ?? ''),
      'package_duration'    => clean($_POST['package_duration'] ?? ''),
      'package_start_date'  => clean($_POST['package_start_date'] ?? ''),
      'package_end_date'    => clean($_POST['package_end_date'] ?? ''),
      'amount_to_be_paid'   => (int)($_POST['amount_to_be_paid'] ?? 0),
      'amount_paid'         => (int)($_POST['amount_paid'] ?? 0),
      'customer_type'       => clean($_POST['customer_type'] ?? ($current['customer_type'] ?? 'new')),
      'payment_method'      => clean($_POST['payment_method'] ?? ''),
      'commission_amount'   => (int)($_POST['commission_amount'] ?? 0),
      'sale_source'         => clean($_POST['sale_source'] ?? 'Manual'),
      'province'            => clean($_POST['province'] ?? 'Punjab'),
      'exam_board'          => clean($_POST['exam_board'] ?? 'PTB'),
      'status'              => clean($_POST['status'] ?? $current['status']),
      'next_payment_date'   => clean($_POST['next_payment_date'] ?? ''),
      'agent_note'          => clean($_POST['agent_note'] ?? ($current['agent_note'] ?? '')),
    ];

    // Recompute due & validate
    $fields['amount_due'] = max(0, $fields['amount_to_be_paid'] - $fields['amount_paid']);

    if ($fields['full_name']==='' || $fields['city']==='' || $fields['school_name']==='' || $fields['module_name']==='') exit('Required text fields missing');
    if (!preg_match('/^\d{11}$/', $fields['phone'])) exit('Phone must be exactly 11 digits');
    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($datePattern, $fields['package_start_date']) || !preg_match($datePattern, $fields['package_end_date'])) exit('Dates must be YYYY-MM-DD');
    if (!in_array($fields['customer_type'], ['new','old'], true)) exit('Customer type invalid');
    if (!in_array($fields['payment_method'], ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) exit('Payment method invalid');
    if ($fields['amount_due'] > 0) {
      if ($fields['next_payment_date'] === '' || !preg_match($datePattern, $fields['next_payment_date'])) exit('Next payment date required when there is amount due');
    } else {
      $fields['next_payment_date'] = null;
    }
    if ($fields['commission_amount'] < 0) exit('Commission cannot be negative');
    if (!in_array($fields['sale_source'], ['Referral','Ad boost','Manual','Old Customer','Sales Officer','Add classes'], true)) exit('Sale source invalid');
    if (!in_array($fields['province'], ['Punjab','AJK','Federal'], true)) exit('Province invalid');
    $valid_boards = ['PTB','AJK','Federal','Afaq','PTB + Federal','Federal + AJK + Afaq','PTB + Afaq','PTB + AJK'];
    if (!in_array($fields['exam_board'], $valid_boards, true)) exit('Exam board invalid');
    if (!in_array($fields['status'], ['pending','approved','rejected'], true)) exit('Status invalid');
    if (mb_strlen($fields['agent_note']) > 250) exit('Note too long (max 250 characters)');

    // Optional: replace receipt
    if (!empty($_FILES['receipt_image']['name'])) {
      $f = $_FILES['receipt_image'];
      if ($f['error'] !== UPLOAD_ERR_OK) exit('Upload error');
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png'])) exit('Only JPG or PNG allowed');
      if ($f['size'] > 3*1024*1024) exit('Max 3MB');

      if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
      foreach (glob($config['upload_dir'].'/sale_'.$id.'.*') as $old) { @unlink($old); }

      $finalExt  = ($ext === 'jpeg') ? 'jpg' : $ext;
      $finalName = 'sale_'.$id.'.'.$finalExt;
      $dest      = $config['upload_dir'].'/'.$finalName;
      if (!move_uploaded_file($f['tmp_name'], $dest)) exit('Failed to save file');

      $fields['receipt_image_path'] = $finalName;
    }

    // Build SQL dynamically
    $cols = [
      'full_name','phone','city','school_name','module_name','package_duration',
      'package_start_date','package_end_date',
      'amount_to_be_paid','amount_paid','amount_due',
      'next_payment_date','customer_type','payment_method',
      'commission_amount','sale_source','province','exam_board','status'
    ];
    $cols[] = 'agent_note';
    if (isset($fields['receipt_image_path'])) { $cols[] = 'receipt_image_path'; }

    $set = implode(', ', array_map(fn($c)=>"$c = ?", $cols));
    $sql = "UPDATE sales SET $set WHERE id = ? LIMIT 1";

    $vals = array_map(fn($c)=>$fields[$c], $cols);
    $vals[] = $id;

    // ---- Perform update and reconcile ledger atomically
    $pdo->beginTransaction();

    $st = $pdo->prepare($sql);
    $st->execute($vals);

    // ---------- LEDGER RECONCILIATION ----------
    // We treat the first ledger row with source in ('new','renewal') as the "initial" row.
    $newStatus = $fields['status'];
    $amt       = (int)$fields['amount_paid'];
    $method    = $fields['payment_method'];
    if (!in_array($method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) {
      $method = 'cash';
    }
    $source = ($fields['customer_type'] === 'old') ? 'renewal' : 'new';

    // Helper: find existing initial ledger row
    $selInit = $pdo->prepare("
      SELECT id FROM sale_payments
      WHERE sale_id = ? AND source IN ('new','renewal')
      ORDER BY id ASC
      LIMIT 1
    ");
    $selInit->execute([$id]);
    $initial = $selInit->fetch();

    if ($newStatus === 'approved') {
      if ($amt > 0) {
        if ($initial) {
          // UPDATE existing initial row to match current edit
          $up = $pdo->prepare("
            UPDATE sale_payments
            SET school_name = ?, phone = ?, amount = ?, method = ?, source = ?, agent_user_id = ?, paid_at = NOW()
            WHERE id = ?
          ");
          $up->execute([
            $fields['school_name'],
            $fields['phone'],
            $amt,
            $method,
            $source,
            (int)$current['agent_user_id'], // agent doesn't change here
            (int)$initial['id']
          ]);
        } else {
          // INSERT initial row (if missing)
          $ins = $pdo->prepare("
            INSERT INTO sale_payments
              (sale_id, school_name, phone, amount, method, source, agent_user_id, paid_at, created_at)
            VALUES
              (?,       ?,           ?,     ?,      ?,      ?,      ?,            NOW(),  NOW())
          ");
          $ins->execute([
            $id,
            $fields['school_name'],
            $fields['phone'],
            $amt,
            $method,
            $source,
            (int)$current['agent_user_id']
          ]);
        }
      } else {
        // amount_paid == 0 → ensure no initial row remains
        if ($initial) {
          $pdo->prepare("DELETE FROM sale_payments WHERE id=?")->execute([(int)$initial['id']]);
        }
      }
    } else {
      // Status is pending or rejected → remove ALL ledger rows for this sale
      $pdo->prepare("DELETE FROM sale_payments WHERE sale_id = ?")->execute([$id]);
    }

    $pdo->commit();

    redirect('admin/sales');
  }

  public function payments(){
    require_role('admin');
    global $pdo;

    // Default to current month window (Asia/Karachi) but keep user overrides
    $w = current_month_window();

    $date_from = $_GET['date_from'] ?? substr($w['start'], 0, 10); // 'YYYY-MM-DD'
    $date_to   = $_GET['date_to']   ?? substr($w['end'],   0, 10); // exclusive upper bound

    // Very light validation (YYYY-MM-DD)
    $re = '/^\d{4}-\d{2}-\d{2}$/';
    if (!preg_match($re, $date_from)) $date_from = substr($w['start'], 0, 10);
    if (!preg_match($re, $date_to))   $date_to   = substr($w['end'],   0, 10);

    // Convert to full timestamps for the half-open range
    $fromTs = $date_from . ' 00:00:00';
    $toTs   = $date_to   . ' 00:00:00';

    // Optional search filter
    $searchTerm = trim($_GET['search'] ?? '');
    if (strlen($searchTerm) > 100) {
      $searchTerm = substr($searchTerm, 0, 100);
    }

    // Fetch payments in range
    $sql = "SELECT p.*, u.name AS agent_name
            FROM sale_payments p
            LEFT JOIN users u ON u.id = p.agent_user_id
            WHERE p.paid_at >= ? AND p.paid_at < ?";
    $params = [$fromTs, $toTs];
    if ($searchTerm !== '') {
      $sql .= " AND (p.school_name LIKE ? OR p.phone LIKE ?)";
      $like = '%'.$searchTerm.'%';
      $params[] = $like;
      $params[] = $like;
    }
    $sql .= " ORDER BY p.paid_at DESC, p.id DESC";
    $st = $pdo->prepare($sql);
    $st->execute($params);
    $payments = $st->fetchAll();

    // Totals
    $totalAmount = 0;
    $bySource = ['new' => 0, 'renewal' => 0, 'due' => 0];
    foreach ($payments as $p) {
      $amt = (int)$p['amount'];
      $totalAmount += $amt;
      $src = $p['source'] ?? 'new';
      if (!isset($bySource[$src])) $bySource[$src] = 0;
      $bySource[$src] += $amt;
    }

    require __DIR__.'/../../views/admin/payments.php';
  }

  public function approveDuePayment(){
    require_role('admin');
    global $pdo, $config;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    $pdo->beginTransaction();
    $st = $pdo->prepare("
      SELECT dp.*, s.amount_due, s.amount_paid
      FROM sale_due_payments dp
      JOIN sales s ON s.id = dp.sale_id
      WHERE dp.id = ?
      FOR UPDATE
    ");
    $st->execute([$id]);
    $dp = $st->fetch();
    if (!$dp) { $pdo->rollBack(); exit('Due payment not found'); }
    if ($dp['status'] !== 'pending') { $pdo->rollBack(); exit('Already processed'); }

    $amount = (int)$dp['amount'];
    if ($amount <= 0) { $pdo->rollBack(); exit('Invalid amount'); }
    $currentDue = (int)$dp['amount_due'];
    if ($amount > $currentDue) { $pdo->rollBack(); exit('Amount exceeds current due'); }

    $nextDate = $dp['next_payment_date'] ?? null;
    if ($nextDate === '' || $nextDate === '0000-00-00') $nextDate = null;
    $remaining = $currentDue - $amount;
    if ($remaining > 0) {
      if (!$nextDate || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $nextDate)) {
        $pdo->rollBack();
        exit('Next payment date required');
      }
    } else {
      $nextDate = null;
    }

    // Update sale balances
    $pdo->prepare("
      UPDATE sales
      SET amount_paid = amount_paid + ?,
          amount_due  = GREATEST(amount_due - ?, 0),
          next_payment_date = ?
      WHERE id = ? LIMIT 1
    ")->execute([$amount, $amount, $nextDate, (int)$dp['sale_id']]);

    // Insert ledger entry
    $ins = $pdo->prepare("
      INSERT INTO sale_payments
        (sale_id, school_name, phone, amount, method, source, agent_user_id, paid_at, created_at, receipt_path)
      VALUES
        (?, ?, ?, ?, ?, 'due', ?, NOW(), NOW(), NULL)
    ");
    $ins->execute([
      (int)$dp['sale_id'],
      $dp['school_name'] ?? '',
      $dp['phone'] ?? '',
      $amount,
      $dp['method'],
      (int)$dp['agent_user_id']
    ]);
    $paymentId = (int)$pdo->lastInsertId();

    $finalReceipt = null;
    if (!empty($dp['receipt_path'])) {
      $src = rtrim($config['upload_dir'],'/').'/'.$dp['receipt_path'];
      if (is_file($src)) {
        $ext = strtolower(pathinfo($dp['receipt_path'], PATHINFO_EXTENSION));
        $finalReceipt = 'pay_'.$paymentId.'.'.($ext === '' ? 'jpg' : $ext);
        $dest = rtrim($config['upload_dir'],'/').'/'.$finalReceipt;
        if (@rename($src, $dest)) {
          $pdo->prepare("UPDATE sale_payments SET receipt_path=? WHERE id=?")->execute([$finalReceipt, $paymentId]);
        } else {
          $finalReceipt = $dp['receipt_path'];
          $pdo->prepare("UPDATE sale_payments SET receipt_path=? WHERE id=?")->execute([$finalReceipt, $paymentId]);
        }
      }
    }

    $pdo->prepare("
      UPDATE sale_due_payments
      SET status='approved',
          reviewed_by_user_id=?,
          reviewed_at=NOW(),
          reviewer_note=?,
          receipt_path=?,
          payment_id=?
      WHERE id = ? LIMIT 1
    ")->execute([ (int)user()['id'], clean($_POST['note'] ?? ''), $finalReceipt, $paymentId, $id ]);

    $pdo->commit();
    redirect('admin/sales');
  }

  public function rejectDuePayment(){
    require_role('admin');
    global $pdo;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');
    $id = (int)($_POST['id'] ?? 0);
    $note = clean($_POST['note'] ?? '');
    if ($id <= 0) exit('Invalid ID');
    if ($note === '') exit('Rejection note required');

    $st = $pdo->prepare("
      UPDATE sale_due_payments
      SET status='rejected',
          reviewer_note = ?,
          reviewed_by_user_id = ?,
          reviewed_at = NOW()
      WHERE id = ? AND status='pending'
      LIMIT 1
    ");
    $st->execute([$note, (int)user()['id'], $id]);
    if ($st->rowCount() === 0) exit('Already processed or not found');

    redirect('admin/sales');
  }
}

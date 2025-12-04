<?php
class AgentController {

public function dashboard(){
  require_role('agent');
  global $pdo;

  // Build window from query (?date_from=YYYY-MM-DD&date_to=YYYY-MM-DD), default = this month
  [$dateFrom, $dateTo] = window_from_query();
  $windowLabel = window_label($dateFrom, $dateTo);
  // Half-open range with explicit times
  $wStart = $dateFrom.' 00:00:00';
  $wEnd   = $dateTo.' 00:00:00';

  // Sales for this window
  $sql = "SELECT *
          FROM sales
          WHERE agent_user_id = ?
            AND created_at >= ?
            AND created_at <  ?
          ORDER BY id DESC";
  $st = $pdo->prepare($sql);
  $st->execute([ user()['id'], $wStart, $wEnd ]);
  $sales = $st->fetchAll();

  // Fetch latest rejection notes for rejected sales
  $rejectionNotes = [];
  $rejectedIds = array_column(array_filter($sales, fn($row)=>($row['status'] ?? '') === 'rejected'), 'id');
  if (!empty($rejectedIds)) {
    $placeholders = implode(',', array_fill(0, count($rejectedIds), '?'));
    $sqlNotes = "
      SELECT sale_id, note
      FROM approval_logs
      WHERE action='rejected' AND sale_id IN ($placeholders)
      ORDER BY id DESC
    ";
    $stNotes = $pdo->prepare($sqlNotes);
    $stNotes->execute(array_map('intval', $rejectedIds));
    foreach ($stNotes->fetchAll() as $row) {
      $sid = (int)$row['sale_id'];
      if (!isset($rejectionNotes[$sid])) {
        $rejectionNotes[$sid] = $row['note'];
      }
    }
  }

  // Latest due payment submissions for this agent
  $stPend = $pdo->prepare("
    SELECT dp.*, s.school_name AS sale_school, s.phone AS sale_phone
    FROM sale_due_payments dp
    LEFT JOIN sales s ON s.id = dp.sale_id
    WHERE dp.agent_user_id = ?
      AND dp.created_at >= ?
      AND dp.created_at <  ?
    ORDER BY dp.id DESC
    LIMIT 25
  ");
  $stPend->execute([ user()['id'], $wStart, $wEnd ]);
  $myDuePayments = $stPend->fetchAll();

  // Money I received in this window (from payments ledger)
  $st2 = $pdo->prepare("
    SELECT COALESCE(SUM(amount),0)
    FROM sale_payments
    WHERE agent_user_id = ?
      AND paid_at >= ?
      AND paid_at <  ?
  ");
  $st2->execute([ user()['id'], $wStart, $wEnd ]);
  $myIncomeInRange = (int)$st2->fetchColumn();

  // My commissions on approved sales created in this window
  $st3 = $pdo->prepare("
    SELECT COALESCE(SUM(commission_amount),0)
    FROM sales
    WHERE agent_user_id = ?
      AND status='approved'
      AND created_at >= ?
      AND created_at <  ?
  ");
  $st3->execute([ user()['id'], $wStart, $wEnd ]);
  $myCommissionInRange = (int)$st3->fetchColumn();

  $window = [
    'from'  => $dateFrom,
    'to'    => $dateTo,
    'label' => $windowLabel,
  ];

  require __DIR__.'/../../views/agent/dashboard.php';
}

  public function editDuePayment(){
    require_role('agent'); global $pdo;

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    $st = $pdo->prepare("
      SELECT dp.*, s.amount_due AS sale_due, s.school_name AS sale_school, s.phone AS sale_phone
      FROM sale_due_payments dp
      LEFT JOIN sales s ON s.id = dp.sale_id
      WHERE dp.id = ? AND dp.agent_user_id = ?
      LIMIT 1
    ");
    $st->execute([$id, user()['id']]);
    $dp = $st->fetch();
    if (!$dp) { http_response_code(404); exit('Not found'); }
    if (($dp['status'] ?? '') !== 'rejected') exit('Only rejected due payments can be edited');

    require __DIR__.'/../../views/agent/edit_due_payment.php';
  }

  public function updateDuePayment(){
    require_role('agent'); global $pdo, $config;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) exit('Invalid ID');

    $st = $pdo->prepare("
      SELECT dp.*, s.amount_due AS sale_due
      FROM sale_due_payments dp
      JOIN sales s ON s.id = dp.sale_id
      WHERE dp.id = ? AND dp.agent_user_id = ?
      LIMIT 1
    ");
    $st->execute([$id, user()['id']]);
    $dp = $st->fetch();
    if (!$dp) { http_response_code(404); exit('Not found'); }
    if (($dp['status'] ?? '') !== 'rejected') exit('Only rejected due payments can be edited');

    $amount = (int)($_POST['amount'] ?? 0);
    $method = clean($_POST['method'] ?? 'cash');
    $npd    = clean($_POST['next_payment_date'] ?? '');
    $note   = clean($_POST['agent_note'] ?? '');

    if ($amount <= 0) exit('Amount must be > 0');
    if (!in_array($method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) $method = 'cash';
    if (mb_strlen($note) > 250) exit('Note too long (max 250 characters)');

    $currentDue = (int)($dp['sale_due'] ?? 0);
    if ($currentDue <= 0) exit('No outstanding due remains');
    if ($amount > $currentDue) exit('Amount cannot exceed current due');

    if ($currentDue - $amount > 0) {
      if ($npd === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $npd)) exit('Next payment date required');
    } else {
      $npd = null;
    }

    $receiptName = $dp['receipt_path'] ?? null;
    if (!empty($_FILES['receipt']['name'])) {
      $f = $_FILES['receipt'];
      if ($f['error'] !== UPLOAD_ERR_OK) exit('Receipt upload error');
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if ($ext === 'jpeg') $ext = 'jpg';
      if (!in_array($ext, ['jpg','png'])) exit('Only JPG or PNG allowed for receipt');
      if ($f['size'] > 3*1024*1024) exit('Receipt max size is 3MB');
      if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
      $receiptName = 'duepend_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
      $dest = rtrim($config['upload_dir'],'/').'/'.$receiptName;
      if (!move_uploaded_file($f['tmp_name'], $dest)) {
        $receiptName = $dp['receipt_path'] ?? null;
      }
    }

    $payableSnapshot  = $currentDue;
    $remainingSnapshot = max($currentDue - $amount, 0);
    $pdo->prepare("
      UPDATE sale_due_payments
      SET amount = ?, method = ?, next_payment_date = ?, agent_note = ?, receipt_path = ?, status = 'pending',
          reviewer_note = NULL, reviewed_by_user_id = NULL, reviewed_at = NULL,
          payable_at_request = ?, remaining_at_request = ?
      WHERE id = ? AND agent_user_id = ?
      LIMIT 1
    ")->execute([
      $amount,
      $method,
      $npd,
      $note,
      $receiptName,
      $payableSnapshot,
      $remainingSnapshot,
      $id,
      (int)user()['id']
    ]);

    redirect('agent/dashboard');
  }


  public function create(){
    require_role('agent'); global $pdo;
    $boards = $pdo->query("SELECT name FROM exam_boards ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);

    // Read preferred type from query (?type=new|old), default = new
    $initialType = (isset($_GET['type']) && $_GET['type'] === 'old') ? 'old' : 'new';

    // Make available to the view
    require __DIR__.'/../../views/agent/create_sale.php';
  }

  public function edit(){
    require_role('agent'); global $pdo;

    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) exit('Invalid sale ID');

    $st = $pdo->prepare("SELECT * FROM sales WHERE id = ? AND agent_user_id = ? LIMIT 1");
    $st->execute([$id, user()['id']]);
    $sale = $st->fetch();
    if (!$sale) { http_response_code(404); exit('Sale not found'); }
    if (($sale['status'] ?? '') !== 'rejected') exit('Only rejected submissions can be edited');

    $boards = $pdo->query("SELECT name FROM exam_boards ORDER BY name")->fetchAll(PDO::FETCH_COLUMN);
    $initialType = ($sale['customer_type'] ?? 'new') === 'old' ? 'old' : 'new';
    $formAction = base_url('agent/sales/update');

    require __DIR__.'/../../views/agent/create_sale.php';
  }

  public function update(){
    require_role('agent'); global $pdo, $config;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $saleId = (int)($_POST['sale_id'] ?? 0);
    if ($saleId <= 0) exit('Invalid sale ID');

    $st = $pdo->prepare("SELECT * FROM sales WHERE id = ? AND agent_user_id = ? LIMIT 1");
    $st->execute([$saleId, user()['id']]);
    $current = $st->fetch();
    if (!$current) { http_response_code(404); exit('Sale not found'); }
    if (($current['status'] ?? '') !== 'rejected') exit('Only rejected submissions can be edited');

    $customer_type     = clean($_POST['customer_type'] ?? ($current['customer_type'] ?? 'new'));
    $isOld             = ($customer_type === 'old');

    $school_name       = clean($_POST['school_name'] ?? '');
    $phone             = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    if ($school_name === '' || !preg_match('/^\d{11}$/', $phone)) exit('School and valid 11-digit phone are required');

    $existing_due      = $this->dueForSchoolPhone($school_name, $phone);

    $full_name         = clean($_POST['full_name'] ?? '');
    $city              = clean($_POST['city'] ?? '');
    $module_name       = clean($_POST['module_name'] ?? '');
    $package_duration  = clean($_POST['package_duration'] ?? '');
    $pkg_start         = clean($_POST['package_start_date'] ?? '');
    $pkg_end           = clean($_POST['package_end_date'] ?? '');
    $amount_to_pay     = (int)($_POST['amount_to_be_paid'] ?? 0);
    $amount_paid       = (int)($_POST['amount_paid'] ?? 0);
    $commission_amount = (int)($_POST['commission_amount'] ?? 0);
    $sale_source       = clean($_POST['sale_source'] ?? ($current['sale_source'] ?? 'Manual'));
    $province          = clean($_POST['province'] ?? ($current['province'] ?? 'Punjab'));
    $exam_board        = clean($_POST['exam_board'] ?? ($current['exam_board'] ?? 'PTB'));
    $next_payment_date = clean($_POST['next_payment_date'] ?? '');
    $payment_method    = clean($_POST['payment_method'] ?? ($current['payment_method'] ?? 'cash'));
    $agent_note        = clean($_POST['agent_note'] ?? '');
    $agent_name        = user()['name'];

    if (!in_array($customer_type, ['new','old'], true)) exit('Invalid customer type');

    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

    if ($customer_type === 'new') {
      if ($this->schoolPhoneExists($school_name, $phone, $saleId, ['approved'])) {
        exit('This school & phone already exists in our database. Use customer type "Old". Go Back');
      }
      if ($existing_due > 0) {
        exit('This school (with this phone) has outstanding dues. Clear dues before editing this submission.');
      }
      if ($full_name==='' || $city==='' || $module_name==='') exit('Required fields missing');
      if (!preg_match($datePattern, $pkg_start) || !preg_match($datePattern, $pkg_end)) exit('Dates must be YYYY-MM-DD');
      if ($commission_amount < 0) exit('Commission cannot be negative');
    }

    if ($customer_type === 'old') {
      if ($existing_due > 0) {
        exit('Outstanding due exists. Record a payment against due instead of renewal.');
      }
      $commission_amount = 0;
      if ($module_name==='') exit('Module is required for renewal');
      if (!preg_match($datePattern, $pkg_start) || !preg_match($datePattern, $pkg_end)) exit('Dates must be YYYY-MM-DD');
      if ($amount_to_pay <= 0) exit('Payable must be greater than 0 for renewal');
    }

    if ($full_name==='' || $city==='' || $school_name==='' || $module_name==='') exit('Required text fields missing');
    if (!preg_match($datePattern, $pkg_start) || !preg_match($datePattern, $pkg_end)) exit('Dates must be YYYY-MM-DD');
    if (!preg_match('/^\d{11}$/', $phone)) exit('Phone must be exactly 11 digits');
    if ($amount_to_pay < 0 || $amount_paid < 0) exit('Amounts cannot be negative');
    if ($customer_type === 'old' && $amount_paid > $amount_to_pay) exit('Paid cannot exceed payable');
    $amount_due = max(0, $amount_to_pay - $amount_paid);
    if ($amount_due > 0) {
      if ($next_payment_date === '' || !preg_match($datePattern, $next_payment_date)) {
        exit('Next payment date required when there is amount due');
      }
    } else {
      $next_payment_date = null;
    }
    if (!in_array($payment_method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) exit('Invalid payment method');
    if ($commission_amount < 0) exit('Commission cannot be negative');
    if (!in_array($sale_source, ['Referral','Ad boost','Manual','Old Customer','Sales Officer','Add classes'], true)) exit('Invalid sale source');
    if (!in_array($province, ['Punjab','AJK','Federal'], true)) exit('Invalid province');
    if (mb_strlen($agent_note) > 250) exit('Note too long (max 250 characters)');

    $receiptPath = $current['receipt_image_path'] ?? null;
    if (!empty($_FILES['receipt_image']['name'])) {
      $f = $_FILES['receipt_image'];
      if ($f['error'] !== UPLOAD_ERR_OK) exit('Upload error');
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png'])) exit('Only JPG or PNG allowed');
      if ($f['size'] > 3*1024*1024) exit('Max 3MB');
      if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
      foreach (glob($config['upload_dir'].'/sale_'.$saleId.'.*') as $old) { @unlink($old); }
      $finalExt  = ($ext === 'jpeg') ? 'jpg' : $ext;
      $finalName = 'sale_'.$saleId.'.'.$finalExt;
      $dest      = $config['upload_dir'].'/'.$finalName;
      if (!move_uploaded_file($f['tmp_name'], $dest)) exit('Failed to save file');
      $receiptPath = $finalName;
    }

    $sql = "UPDATE sales SET
              full_name = ?, phone = ?, city = ?, school_name = ?, module_name = ?, package_duration = ?,
              package_start_date = ?, package_end_date = ?, amount_to_be_paid = ?, amount_paid = ?, amount_due = ?,
              next_payment_date = ?, customer_type = ?, payment_method = ?, agent_name = ?, commission_amount = ?,
              sale_source = ?, province = ?, exam_board = ?, agent_note = ?, status = 'pending'";
    $vals = [
      $full_name, $phone, $city, $school_name, $module_name, $package_duration,
      $pkg_start, $pkg_end, $amount_to_pay, $amount_paid, $amount_due,
      $next_payment_date, $customer_type, $payment_method, $agent_name, $commission_amount,
      $sale_source, $province, $exam_board, $agent_note
    ];
    if ($receiptPath !== null) {
      $sql .= ", receipt_image_path = ?";
      $vals[] = $receiptPath;
    }
    $sql .= " WHERE id = ? LIMIT 1";
    $vals[] = $saleId;

    $st = $pdo->prepare($sql);
    $st->execute($vals);

    redirect('agent/dashboard');
  }

  /* ----------------- Helpers ----------------- */

  // Current outstanding due from the LATEST approved sale for (school+phone)
  private function dueForSchoolPhone(string $school, string $phone): int {
    global $pdo;
    $st = $pdo->prepare("SELECT amount_due
                         FROM sales
                         WHERE status='approved' AND school_name=? AND phone=?
                         ORDER BY id DESC
                         LIMIT 1");
    $st->execute([$school, $phone]);
    $row = $st->fetch();
    return $row ? (int)$row['amount_due'] : 0;
  }

  // Does an entry exist with the exact pair (school_name + phone) ?  (any status)
  private function schoolPhoneExists(string $school, string $phone, ?int $excludeId = null, ?array $statuses = null): bool {
    global $pdo;
    $sql = "SELECT 1 FROM sales WHERE school_name=? AND phone=?";
    $vals = [$school, $phone];
    if ($excludeId) {
      $sql .= " AND id <> ?";
      $vals[] = $excludeId;
    }
    if ($statuses && count($statuses) > 0) {
      $in  = implode(',', array_fill(0, count($statuses), '?'));
      $sql .= " AND status IN ($in)";
      foreach ($statuses as $stName) {
        $vals[] = $stName;
      }
    }
    $sql .= " LIMIT 1";
    $st = $pdo->prepare($sql);
    $st->execute($vals);
    return (bool)$st->fetchColumn();
  }

  /* -------------- AJAX lookup (Old: strict pair match) -------------- */
  public function lookupSchool(){
    require_role('agent'); global $pdo;

    $school = trim($_GET['school_name'] ?? '');
    $phone  = preg_replace('/\D+/', '', $_GET['phone'] ?? '');

    header('Content-Type: application/json');

    // Now we REQUIRE both for Old fetch (to avoid ambiguity)
    if ($school === '' || !preg_match('/^\d{11}$/', $phone)) {
      echo json_encode(['ok'=>false,'msg'=>'Enter BOTH school name and a valid 11-digit phone to fetch.']);
      exit;
    }

    // Exact match (latest approved) for this pair
    $st = $pdo->prepare("SELECT full_name,phone,city,school_name,module_name,
                                package_start_date,package_end_date,amount_to_be_paid
                         FROM sales
                         WHERE status='approved' AND school_name=? AND phone=?
                         ORDER BY id DESC LIMIT 1");
    $st->execute([$school, $phone]);
    $row = $st->fetch();

    // Compute due for the exact pair
    $due = $this->dueForSchoolPhone($school, $phone);

    if ($row) {
      echo json_encode([
        'ok'     => true,
        'status' => 'exact',
        'data'   => [
          'full_name'  => $row['full_name'],
          'phone'      => $row['phone'],
          'city'       => $row['city'],
          'school'     => $row['school_name'],
          'module'     => $row['module_name'],
          'pkg_start'  => $row['package_start_date'],
          'pkg_end'    => $row['package_end_date'],
          'last_price' => (int)$row['amount_to_be_paid'],
          'due'        => (int)$due,
        ]
      ]);
    } else {
      // No approved history for this exact pair — still return pair + due (0)
      echo json_encode([
        'ok'     => true,
        'status' => 'none',
        'data'   => [
          'full_name'  => '',
          'phone'      => $phone,
          'city'       => '',
          'school'     => $school,
          'module'     => '',
          'pkg_start'  => '',
          'pkg_end'    => '',
          'last_price' => 0,
          'due'        => (int)$due,
        ]
      ]);
    }
    exit;
  }

  /* --------- Pay outstanding due (by exact pair; no new row) --------- */
  public function payDue(){
  require_role('agent'); global $pdo, $config;

  if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

  $school_name       = clean($_POST['school_name'] ?? '');
  $phone             = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
  $paid              = (int)($_POST['amount_paid'] ?? 0);
  $next_payment_date = clean($_POST['next_payment_date'] ?? '');
  $method            = clean($_POST['paydue_payment_method'] ?? 'cash');
  $agentNote         = clean($_POST['due_note'] ?? '');

  if ($school_name === '' || !preg_match('/^\d{11}$/', $phone)) exit('School and valid 11-digit phone are required');
  if ($paid <= 0) exit('Paid must be > 0');
  if (!in_array($method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) {
    $method = 'cash';
  }
  if (mb_strlen($agentNote) > 250) exit('Note too long (max 250 characters)');

  // Find latest approved sale for the exact pair
  $st = $pdo->prepare("SELECT id, amount_due
                       FROM sales
                       WHERE status='approved' AND school_name=? AND phone=?
                       ORDER BY id DESC
                       LIMIT 1");
  $st->execute([$school_name, $phone]);
  $sale = $st->fetch();
  if (!$sale) exit('No approved record found for this school/phone pair');
  if ((int)$sale['amount_due'] <= 0) exit('This school has no outstanding due');
  if ($paid > (int)$sale['amount_due']) exit('Paid cannot exceed current due');

  $remaining = (int)$sale['amount_due'] - $paid;
  $payableSnapshot  = (int)$sale['amount_due'];
  $remainingSnapshot = max($payableSnapshot - $paid, 0);
  if ($remaining > 0) {
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $next_payment_date)) {
      exit('Next payment date required (YYYY-MM-DD)');
    }
  } else {
    $next_payment_date = null;
  }

  $receiptName = null;
  if (!empty($_FILES['paydue_receipt']['name'])) {
    $f = $_FILES['paydue_receipt'];
    if ($f['error'] !== UPLOAD_ERR_OK) exit('Receipt upload error');
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if ($ext === 'jpeg') $ext = 'jpg';
    if (!in_array($ext, ['jpg','png'])) exit('Only JPG or PNG allowed for receipt');
    if ($f['size'] > 3*1024*1024) exit('Receipt max size is 3MB');

    if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
    $receiptName = 'duepend_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
    $dest = rtrim($config['upload_dir'],'/').'/'.$receiptName;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
      $receiptName = null;
    }
  }

  $sql = "
    INSERT INTO sale_due_payments
      (sale_id, school_name, phone, amount, method, agent_user_id, next_payment_date, agent_note, receipt_path, payable_at_request, remaining_at_request, status, created_at)
    VALUES
      (?,       ?,           ?,     ?,      ?,      ?,             ?,               ?,          ?,                   ?,                   ?,            'pending', NOW())
  ";
  $pdo->prepare($sql)->execute([
    (int)$sale['id'],
    $school_name,
    $phone,
    $paid,
    $method,
    (int)user()['id'],
    $next_payment_date,
    $agentNote,
    $receiptName,
    $payableSnapshot,
    $remainingSnapshot
  ]);

  redirect('agent/dashboard');
}


  /* --------- Store: New / Old-renewal (+ fallback for Old-due) --------- */
  public function store(){
    require_role('agent');
    global $pdo, $config;

    if (!csrf_check($_POST['csrf'] ?? '')) exit('Bad CSRF');

    $customer_type     = clean($_POST['customer_type'] ?? 'new'); // 'new' | 'old'
    $isOld             = ($customer_type === 'old');

    $school_name       = clean($_POST['school_name'] ?? '');
    $phone             = preg_replace('/\D+/', '', $_POST['phone'] ?? '');
    if ($school_name === '' || !preg_match('/^\d{11}$/', $phone)) exit('School and valid 11-digit phone are required');

    // Use strict pair for due
    $existing_due      = $this->dueForSchoolPhone($school_name, $phone);

    $full_name         = clean($_POST['full_name'] ?? '');
    $city              = clean($_POST['city'] ?? '');
    $module_name       = clean($_POST['module_name'] ?? '');
$package_duration  = clean($_POST['package_duration'] ?? '');

    $pkg_start         = clean($_POST['package_start_date'] ?? '');
    $pkg_end           = clean($_POST['package_end_date'] ?? '');

    $amount_to_pay     = (int)($_POST['amount_to_be_paid'] ?? 0);
    $amount_paid       = (int)($_POST['amount_paid'] ?? 0);
    $commission_amount = (int)($_POST['commission_amount'] ?? 0);
    $sale_source       = clean($_POST['sale_source'] ?? 'Manual');
    $province          = clean($_POST['province'] ?? 'Punjab');
    $exam_board        = clean($_POST['exam_board'] ?? 'PTB');
    $agent_note        = clean($_POST['agent_note'] ?? '');

    $next_payment_date = clean($_POST['next_payment_date'] ?? '');

    $payment_method    = clean($_POST['payment_method'] ?? '');
    $agent_name        = user()['name'];
    $agent_user_id     = user()['id'];
    if (mb_strlen($agent_note) > 250) exit('Note too long (max 250 characters)');

    if (!in_array($customer_type, ['new','old'], true)) exit('Invalid customer type');

    $datePattern = '/^\d{4}-\d{2}-\d{2}$/';

    /* --- Fallback: if someone posts Old + due to store(), handle it (pair-based) --- */
    if ($customer_type === 'old' && (($_POST['old_mode'] ?? '') === 'due')) {
      if ($existing_due <= 0) exit('This school has no outstanding due');

      $paid = (int)($_POST['amount_paid'] ?? 0);
      if ($paid <= 0) exit('Paid must be > 0');
      if ($paid > $existing_due) exit('Paid cannot exceed current due');

      $npd = $next_payment_date;
      $remaining = $existing_due - $paid;
      if ($remaining > 0) {
        if (!preg_match($datePattern, $npd)) exit('Next payment date required (YYYY-MM-DD)');
      } else {
        $npd = null;
      }

      $st = $pdo->prepare("SELECT id
                           FROM sales
                           WHERE status='approved' AND school_name=? AND phone=?
                           ORDER BY id DESC LIMIT 1");
      $st->execute([$school_name, $phone]);
      $row = $st->fetch();
      if (!$row) exit('No approved record found for this school/phone pair');

      $dueNote = clean($_POST['due_note'] ?? '');
      if (mb_strlen($dueNote) > 250) exit('Note too long (max 250 characters)');

      $receiptName = null;
      if (!empty($_FILES['paydue_receipt']['name'])) {
        $f = $_FILES['paydue_receipt'];
        if ($f['error'] !== UPLOAD_ERR_OK) exit('Receipt upload error');
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        if ($ext === 'jpeg') $ext = 'jpg';
        if (!in_array($ext, ['jpg','png'])) exit('Only JPG or PNG allowed for receipt');
        if ($f['size'] > 3*1024*1024) exit('Receipt max size is 3MB');
        if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
        $receiptName = 'duepend_'.time().'_'.bin2hex(random_bytes(4)).'.'.$ext;
        $dest = rtrim($config['upload_dir'],'/').'/'.$receiptName;
        if (!move_uploaded_file($f['tmp_name'], $dest)) {
          $receiptName = null;
        }
      }

      $payableSnapshot  = $existing_due;
      $remainingSnapshot = max($existing_due - $paid, 0);
      $pdo->prepare("
        INSERT INTO sale_due_payments
          (sale_id, school_name, phone, amount, method, agent_user_id, next_payment_date, agent_note, receipt_path, payable_at_request, remaining_at_request, status, created_at)
        VALUES
          (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
      ")->execute([
        (int)$row['id'],
        $school_name,
        $phone,
        $paid,
        $payment_method,
        (int)user()['id'],
        $npd,
        $dueNote,
        $receiptName,
        $payableSnapshot,
        $remainingSnapshot
      ]);

      redirect('agent/dashboard');
      return;
    }

    /* ---------- NEW CUSTOMER (block if exact pair exists) ---------- */
    if ($customer_type === 'new') {

      // NEW is forbidden if EXACT pair (school+phone) already exists among approved sales
      if ($this->schoolPhoneExists($school_name, $phone, null, ['approved'])) {
        exit('This school & phone already exists in our database. Use customer type "Old". Go Back');
      }

      if ($existing_due > 0) {
        exit('This school (with this phone) has outstanding dues. Clear dues before adding a new submission.');
      }

      if ($full_name==='' || $city==='' || $module_name==='') exit('Required fields missing');
      if (!preg_match('/^\d{11}$/', $phone)) exit('Phone must be exactly 11 digits');
      if (!preg_match($datePattern, $pkg_start) || !preg_match($datePattern, $pkg_end)) exit('Dates must be YYYY-MM-DD');
      if ($commission_amount < 0) exit('Commission cannot be negative');
    }

    /* ---------- OLD CUSTOMER (RENEWAL ONLY here) ---------- */
    if ($customer_type === 'old') {
      if ($existing_due > 0) {
        exit('Outstanding due exists. Record a payment against due instead of renewal.');
      }

      // Hard lock commission to zero for OLD customers
      $commission_amount = 0;

      if ($module_name==='') exit('Module is required for renewal');
      if (!preg_match($datePattern, $pkg_start) || !preg_match($datePattern, $pkg_end)) exit('Dates must be YYYY-MM-DD');
      if ($amount_to_pay <= 0) exit('Payable must be greater than 0 for renewal');
    }

    if ($amount_to_pay < 0 || $amount_paid < 0) exit('Amounts cannot be negative');
    if ($customer_type === 'old' && $amount_paid > $amount_to_pay) exit('Paid cannot exceed payable');

    $amount_due = max(0, $amount_to_pay - $amount_paid);

    if ($amount_due > 0) {
      if ($next_payment_date === '' || !preg_match($datePattern, $next_payment_date)) {
        exit('Next payment date required when there is amount due');
      }
    } else {
      $next_payment_date = null;
    }

    if (!in_array($sale_source, ['Referral','Ad boost','Manual','Old Customer','Sales Officer','Add classes'], true)) exit('Invalid sale source');
    if (!in_array($province, ['Punjab','AJK','Federal'], true)) exit('Invalid province');
    if (!in_array($payment_method, ['bank_transfer','easypaisa','jazzcash','cash','other'], true)) {
      exit('Invalid payment method');
    }

    // Final guard just before INSERT (defensive)
    if ($isOld) {
      $commission_amount = 0;
    }

    // Receipt upload (optional)
    $receiptPath = null;
    if (!empty($_FILES['receipt_image']['name'])) {
      $f = $_FILES['receipt_image'];
      if ($f['error'] !== UPLOAD_ERR_OK) exit('Upload error');
      $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
      if (!in_array($ext, ['jpg','jpeg','png'])) exit('Only JPG or PNG allowed');
      if ($f['size'] > 3*1024*1024) exit('Max 3MB');
      if (!is_dir($config['upload_dir'])) mkdir($config['upload_dir'], 0775, true);
      $tmpName = time().'_'.bin2hex(random_bytes(4)).'.'.($ext==='jpeg'?'jpg':$ext);
      if (!move_uploaded_file($f['tmp_name'], $config['upload_dir'].'/'.$tmpName)) exit('Failed to save file');
      $receiptPath = $tmpName;
    }

    // Insert
    $sql = "INSERT INTO sales
  (agent_user_id,full_name,phone,city,school_name,module_name,package_duration,
   package_start_date,package_end_date,amount_to_be_paid,amount_paid,amount_due,
   next_payment_date,customer_type,payment_method,agent_name,
   receipt_image_path,commission_amount,sale_source,province,exam_board,agent_note,
   status,created_at)
  VALUES(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'pending',NOW())";

    $pdo->prepare($sql)->execute([
  $agent_user_id,$full_name,$phone,$city,$school_name,$module_name,$package_duration,
  $pkg_start,$pkg_end,$amount_to_pay,$amount_paid,$amount_due,
  $next_payment_date,$customer_type,$payment_method,$agent_name,
  $receiptPath,$commission_amount,$sale_source,$province,$exam_board,$agent_note
]);

    $saleId = (int)$pdo->lastInsertId();

    // Rename receipt file to sale_{ID}.ext
    if ($receiptPath) {
      $src = $config['upload_dir'].'/'.$receiptPath;
      $ext = strtolower(pathinfo($receiptPath, PATHINFO_EXTENSION));
      foreach (glob($config['upload_dir'].'/sale_'.$saleId.'.*') as $old) { @unlink($old); }
      $finalName = 'sale_'.$saleId.'.'.($ext==='jpeg'?'jpg':$ext);
      $dest = $config['upload_dir'].'/'.$finalName;
      @rename($src, $dest);
      $pdo->prepare("UPDATE sales SET receipt_image_path=? WHERE id=?")->execute([$finalName, $saleId]);
    }

    redirect('agent/dashboard');
  }

}

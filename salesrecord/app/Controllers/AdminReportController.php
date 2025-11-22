<?php
class AdminReportController {
  public function annual(){
    require_role('admin'); global $pdo;

    $yw = year_window_from_query();
    $fromDate = $yw['from'];
    $toDate   = $yw['to'];
    $fromTs = $fromDate . ' 00:00:00';
    $toTs   = $toDate   . ' 00:00:00';

    // Build dynamic month keys covering the selected range
    $months = [];
    $startMonth = date('Y-m-01', strtotime($fromDate));
    $lastMonth  = date('Y-m-01', strtotime($toDate.' -1 day'));
    $cursor = new DateTime($startMonth);
    $endBoundary = new DateTime($lastMonth);
    $endBoundary->modify('first day of next month');

    while ($cursor < $endBoundary) {
      $ym = $cursor->format('Y-m');
      $months[$ym] = [
        'label'   => month_short_label($ym),
        'gross'   => 0,
        'comm'    => 0,
        'exp'     => 0,
        'profit'  => 0,
        'methods' => [
          'bank_transfer' => 0,
          'easypaisa'     => 0,
          'jazzcash'      => 0,
          'cash'          => 0,
          'other'         => 0,
        ],
      ];
      $cursor->modify('first day of next month');
    }

    // ---- Gross received (payments ledger) grouped by month
    $sqlGross = "
      SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
      FROM sale_payments
      WHERE paid_at >= ? AND paid_at < ?
      GROUP BY ym
    ";
    $st = $pdo->prepare($sqlGross);
    $st->execute([$fromTs, $toTs]);
    foreach ($st->fetchAll() as $r) {
      $ym = $r['ym'];
      if (isset($months[$ym])) $months[$ym]['gross'] = (int)$r['total'];
    }

    // ---- Agent commissions (approved sales by created_at) grouped by month
    $sqlComm = "
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(commission_amount),0) AS total
      FROM sales
      WHERE status='approved'
        AND created_at >= ? AND created_at < ?
      GROUP BY ym
    ";
    $st = $pdo->prepare($sqlComm);
    $st->execute([$fromTs, $toTs]);
    foreach ($st->fetchAll() as $r) {
      $ym = $r['ym'];
      if (isset($months[$ym])) $months[$ym]['comm'] = (int)$r['total'];
    }

    // ---- Expenses grouped by month
    $sqlExp = "
      SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COALESCE(SUM(amount),0) AS total
      FROM expenses
      WHERE created_at >= ? AND created_at < ?
      GROUP BY ym
    ";
    $st = $pdo->prepare($sqlExp);
    $st->execute([$fromTs, $toTs]);
    foreach ($st->fetchAll() as $r) {
      $ym = $r['ym'];
      if (isset($months[$ym])) $months[$ym]['exp'] = (int)$r['total'];
    }

    // ---- Payment method split grouped by month+method
    $sqlMethods = "
      SELECT DATE_FORMAT(paid_at, '%Y-%m') AS ym, method, COALESCE(SUM(amount),0) AS total
      FROM sale_payments
      WHERE paid_at >= ? AND paid_at < ?
      GROUP BY ym, method
    ";
    $st = $pdo->prepare($sqlMethods);
    $st->execute([$fromTs, $toTs]);
    foreach ($st->fetchAll() as $r) {
      $ym = $r['ym'];
      $method = in_array($r['method'], ['bank_transfer','easypaisa','jazzcash','cash','other'], true) ? $r['method'] : 'other';
      if (isset($months[$ym])) {
        $months[$ym]['methods'][$method] += (int)$r['total'];
      }
    }

    // ---- Compute profit per month & totals
    $totals = [
      'gross'  => 0,
      'comm'   => 0,
      'exp'    => 0,
      'profit' => 0,
      'methods' => [
        'bank_transfer' => 0,
        'easypaisa'     => 0,
        'jazzcash'      => 0,
        'cash'          => 0,
        'other'         => 0,
      ]
    ];

    foreach ($months as $ym => &$row) {
      $row['profit'] = $row['gross'] - $row['comm'] - $row['exp'];
      $totals['gross']  += $row['gross'];
      $totals['comm']   += $row['comm'];
      $totals['exp']    += $row['exp'];
      $totals['profit'] += $row['profit'];
      foreach ($row['methods'] as $k => $v) $totals['methods'][$k] += $v;
    }
    unset($row);

    $report = [
      'year'   => (int)$yw['year'],
      'from'   => $fromDate,
      'to'     => $toDate,
      'months' => $months,
      'totals' => $totals,
    ];

    require __DIR__.'/../../views/admin/reports/annual.php';
  }
}

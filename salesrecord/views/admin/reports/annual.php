<?php $title='Admin • Annual Report'; ob_start(); ?>

<h2 style="margin:0 0 12px 0;">Annual Report</h2>

<form method="get" action="<?= base_url('admin/reports/annual') ?>" class="card" style="padding:10px; margin:8px 0;">
  <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <div>
      <label>From (YYYY-MM-DD)</label>
      <input type="date" name="date_from" value="<?= htmlspecialchars($report['from']) ?>">
    </div>
    <div>
      <label>To (exclusive)</label>
      <input type="date" name="date_to" value="<?= htmlspecialchars($report['to']) ?>">
    </div>
    <div>
      <button type="submit">Apply</button>
      <a class="btn" href="<?= base_url('admin/sales') ?>">Back to Dashboard</a>
    </div>
  </div>
  <div class="help" style="margin-top:6px;">
    Window: <?= htmlspecialchars($report['from']) ?> to <?= htmlspecialchars($report['to']) ?> (exclusive upper bound)
  </div>
</form>

<div class="card" style="margin:8px 0;">
  <table>
    <thead>
      <tr>
        <th>Month</th>
        <th style="text-align:right">Gross</th>
        <th style="text-align:right">Agent Commissions</th>
        <th style="text-align:right">Expenses</th>
        <th style="text-align:right">Profit</th>
        <th style="text-align:right">Bank Transfer</th>
        <th style="text-align:right">EasyPaisa</th>
        <th style="text-align:right">JazzCash</th>
        <th style="text-align:right">Cash</th>
        <th style="text-align:right">Other</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($report['months'] as $ym => $row): ?>
        <tr>
          <td><?= htmlspecialchars($row['label']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['gross']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['comm']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['exp']) ?></td>
          <td style="text-align:right;<?= $row['profit']<0?'color:#b91c1c':'' ?>"><?= number_format((int)$row['profit']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['methods']['bank_transfer']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['methods']['easypaisa']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['methods']['jazzcash']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['methods']['cash']) ?></td>
          <td style="text-align:right"><?= number_format((int)$row['methods']['other']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th style="text-align:right">Totals:</th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['gross']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['comm']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['exp']) ?></th>
        <th style="text-align:right;<?= $report['totals']['profit']<0?'color:#b91c1c':'' ?>"><?= number_format((int)$report['totals']['profit']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['methods']['bank_transfer']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['methods']['easypaisa']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['methods']['jazzcash']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['methods']['cash']) ?></th>
        <th style="text-align:right"><?= number_format((int)$report['totals']['methods']['other']) ?></th>
      </tr>
    </tfoot>
  </table>
  <p class="help" style="margin-top:8px;">
    Gross = sum of payments in ledger by <b>paid_at</b>. Commissions = sum on <b>approved</b> sales by <b>created_at</b>. Profit = Gross − Commissions − Expenses.
  </p>
</div>

<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

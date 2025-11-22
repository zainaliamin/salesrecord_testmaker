<?php $title='Admin • Sales'; ob_start(); ?>

<?php $monthLabel = $kpis['label'] ?? date('F'); ?>
<?php if (isset($kpis)): ?>
<div class="card" style="margin:8px 0 12px 0; display:flex; gap:12px; flex-wrap:wrap;">
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Gross Received (Payments)</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$kpis['gross']) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Agent Commissions</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$kpis['commissions']) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Net After Commissions</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$kpis['net_after_comm']) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Expenses</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$kpis['expenses']) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Profit</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$kpis['profit']) ?></div>
  </div>
  <div style="flex:0; align-self:flex-end;">
    <a href="<?= base_url('admin/expenses') ?>" class="btn">Manage Expenses</a>
  </div>
</div>
<?php endif; ?>

<!-- <?php if (isset($shareholder)): ?>
<div class="card" style="margin:12px 0; display:flex; gap:16px; flex-wrap:wrap;">
  <div style="flex:1; min-width:220px;">
    <div style="font-size:14px; opacity:0.7;">15% of Profit (All-time)</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$shareholder['reserve_gross']) ?></div>
  </div>
  <div style="flex:1; min-width:220px;">
    <div style="font-size:14px; opacity:0.7;">Spent so far</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$shareholder['spent_total']) ?></div>
  </div>
  <div style="flex:1; min-width:220px;">
    <div style="font-size:14px; opacity:0.7;">Available to Spend</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format((int)$shareholder['available']) ?></div>
  </div>
  <div style="flex:0; align-self:flex-end;">
    <a href="<?= base_url('admin/shareholder') ?>" class="btn">Open Shareholder Ledger</a>
  </div>
</div>

<div class="card" style="margin:8px 0; max-width:520px;">
  <h3 style="margin-top:0;">Spend from Shareholder Reserve</h3>
  <form method="post" action="<?= base_url('admin/shareholder/spend') ?>">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
    <div style="display:flex; gap:8px; flex-wrap:wrap;">
      <label style="flex:1; min-width:160px;">
        Amount (PKR)
        <input type="number" name="amount" min="1" max="<?= (int)$shareholder['available'] ?>" required>
      </label>
      <label style="flex:1; min-width:260px;">
        Note (optional)
        <input type="text" name="note" maxlength="200" placeholder="e.g., Sponsorship, office refreshment">
      </label>
    </div>
    <div style="margin-top:8px;">
      <button type="submit" class="btn">Spend</button>
      <span class="help">This affects only the shareholder reserve (all-time), not monthly KPIs.</span>
    </div>
  </form>
</div>
<?php endif; ?> -->


<?php if (!empty($agentCommissions)): ?>
<div class="card" style="margin:12px 0">
  <h3 style="margin:0 0 8px 0">Agent Commissions (<?= htmlspecialchars($monthLabel) ?>)</h3>
  <table id="adminCommissions">
    <thead>
      <tr>
        <th>#</th>
        <th>Agent</th>
        <th>Email</th>
        <th>Sales</th>
        <th style="text-align:right">Commission (PKR)</th>
      </tr>
    </thead>
    <tbody>
  <?php
    $rank = 1;
    $grandCommission = 0;
    $grandSales = 0;
    foreach ($agentCommissions as $ac):
      $sum  = (int)$ac['commission_sum'];
      $cnt  = (int)$ac['sale_count'];
      $grandCommission += $sum;
      $grandSales      += $cnt;
  ?>
    <tr>
      <td><?= $rank++ ?></td>
      <td><?= htmlspecialchars($ac['name']) ?></td>
      <td><?= htmlspecialchars($ac['email']) ?></td>
      <td><?= $cnt ?></td>
      <td style="text-align:right"><?= number_format($sum) ?></td>
    </tr>
  <?php endforeach; ?>
</tbody>
<tfoot>
  <tr>
    <th colspan="3" style="text-align:right">Total (Approved, <?= htmlspecialchars($monthLabel) ?>):</th>
    <th><?= (int)$grandSales ?></th>
    <th style="text-align:right"><?= number_format($grandCommission) ?></th>
  </tr>
</tfoot>

  </table>
  <p class="help">Sums are based on <b>approved</b> sales created this month.</p>
</div>
<?php endif; ?>



<div class="align-top" style="margin:4px 0 0 0;display:flex;justify-content:space-between;align-items:center;">
<h2 style="margin:0">All Submissions | <?= htmlspecialchars($monthLabel) ?></h2>
</div>
<form method="get" action="<?= htmlspecialchars(base_url('admin/sales')) ?>" style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
  <?php if (!empty($_GET['status'])): ?>
    <input type="hidden" name="status" value="<?= htmlspecialchars($_GET['status']) ?>">
  <?php endif; ?>
  <label>From:
    <input type="date" name="date_from" value="<?= htmlspecialchars($kpis['from'] ?? date('Y-m-01')) ?>">
  </label>
  <label>To (exclusive):
    <input type="date" name="date_to" value="<?= htmlspecialchars($kpis['to'] ?? date('Y-m-01', strtotime('first day of next month'))) ?>">
  </label>
  <button type="submit">Apply</button>
  <a href="<?= htmlspecialchars(base_url('admin/sales'.(!empty($_GET['status']) ? '?status='.urlencode($_GET['status']) : ''))) ?>">Reset</a>
</form>

<h3 style="margin:6px 0;">Totals (<?= htmlspecialchars($kpis['label'] ?? 'This Month') ?>)</h3>

<p>
  <a href="<?= base_url('admin/sales') ?>">All</a> |
  <a href="<?= base_url('admin/sales?status=pending') ?>">Pending</a> |
  <a href="<?= base_url('admin/sales?status=approved') ?>">Approved</a> |
  <a href="<?= base_url('admin/sales?status=rejected') ?>">Rejected</a>
</p>

<?php
// Totals (Approved only among currently listed rows)
$tPaid = $tToPay = $tDue = $tComm = 0;
foreach ($sales as $row) {
  if (($row['status'] ?? '') === 'approved') {
    $tPaid  += (int)$row['amount_paid'];
    $tToPay += (int)$row['amount_to_be_paid'];
    $tDue   += (int)$row['amount_due'];
    $tComm  += (int)$row['commission_amount'];
  }
}
?>

<table id="adminSales">
  <thead>
    <tr>
      <th>ID</th>
      <th>Agent</th>
      <th>Full Name</th>
      <th>Phone</th>
      <th>School</th>
      <th>Module</th>
      <th>Payable</th>
      <th>Paid</th>
      <th>Due</th>
      <th>Commission</th>
      <th>Status</th>
      <th>Actions</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($sales as $s): ?>
    <tr>
      <td>#<?= (int)$s['id'] ?></td>
      <td><?= htmlspecialchars($s['agent_user_name'] ?? $s['agent_name'] ?? '-') ?></td>
      <td><?= htmlspecialchars($s['full_name']) ?></td>
      <td><?= htmlspecialchars($s['phone']) ?></td>
      <td><?= htmlspecialchars($s['school_name']) ?></td>
      <td><?= htmlspecialchars($s['module_name']) ?></td>
      <td><?= number_format((int)$s['amount_to_be_paid']) ?></td>
      <td><?= number_format((int)$s['amount_paid']) ?></td>
      <td><?= number_format((int)$s['amount_due']) ?></td>
      <td><?= number_format((int)$s['commission_amount']) ?></td>
      <td><span class="badge <?= $s['status'] ?>"><?= $s['status'] ?></span></td>
      <td>
        <a href="<?= base_url('admin/sales/show?id='.$s['id']) ?>">Open</a> |
        <a href="<?= base_url('admin/sales/edit?id='.$s['id']) ?>">Edit</a>
      </td>
    </tr>
  <?php endforeach; ?>

  </tbody>
  <tfoot>
    <tr>
      <th colspan="6" style="text-align:right">Totals (<?= htmlspecialchars($monthLabel) ?>, Approved only):</th>
      <th><?= number_format($tToPay) ?></th>
      <th><?= number_format($tPaid) ?></th>
      <th><?= number_format($tDue) ?></th>
      <th><?= number_format($tComm) ?></th>
      <th colspan="2"></th>
    </tr>
  </tfoot>
</table>

<?php $duePayments = $duePayments ?? []; ?>
<?php if (!empty($duePayments)): ?>
<br>
<div class="card" style="margin-top:12px">
  <h3 style="margin-top:0;">Due Payment Requests</h3>
  <table id="adminDuePayments">
    <thead>
      <tr>
        <th>ID</th>
        <th>Agent</th>
        <th>School</th>
        <th>Phone</th>
        <th>Payable</th>
        <th>Paid</th>
        <th>Remaining</th>
        <th>Method</th>
        <th>Status</th>
        <th>Notes</th>
        <th>Receipt</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($duePayments as $dp): ?>
        <?php
          $paidAmt    = (int)($dp['amount'] ?? 0);
          $payableSnap = isset($dp['payable_at_request']) ? (int)$dp['payable_at_request'] : (int)($dp['current_amount_due'] ?? 0);
          $remainingSnap = isset($dp['remaining_at_request']) ? (int)$dp['remaining_at_request'] : max($payableSnap - $paidAmt, 0);
        ?>
        <tr class="due-payment-row">
          <td>#<?= (int)$dp['id'] ?></td>
          <td><?= htmlspecialchars($dp['agent_name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($dp['school_name'] ?? $dp['sale_school'] ?? '-') ?></td>
          <td><?= htmlspecialchars($dp['phone'] ?? $dp['sale_phone'] ?? '-') ?></td>
          <td><?= number_format($payableSnap) ?></td>
          <td><?= number_format($paidAmt) ?></td>
          <td><?= number_format($remainingSnap) ?></td>
          <td><?= htmlspecialchars($dp['method']) ?></td>
          <td><span class="badge <?= htmlspecialchars($dp['status']) ?>"><?= htmlspecialchars($dp['status']) ?></span></td>
          <td>
            <?php
              $agentText = ($dp['agent_note'] ?? '') !== '' ? 'Agent: '.htmlspecialchars($dp['agent_note']) : 'Agent: —';
              $adminText = 'Admin: '.(isset($dp['reviewer_note']) && $dp['reviewer_note'] !== '' ? htmlspecialchars($dp['reviewer_note']) : '—');
              $fullNote  = $agentText . "\n" . $adminText;
            ?>
            <?php if (($dp['agent_note'] ?? '') !== '' || ($dp['reviewer_note'] ?? '') !== ''): ?>
              <a href="#" class="view-note" data-note="<?= htmlspecialchars($fullNote, ENT_QUOTES) ?>">View Note</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($dp['receipt_path'])): ?>
              <a href="<?= base_url('public/proofs/'.rawurlencode($dp['receipt_path'])) ?>" target="_blank">View</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td style="white-space:nowrap;">
            <?php if (($dp['status'] ?? '') === 'pending'): ?>
              <form method="post" action="<?= base_url('admin/due-payments/approve') ?>" style="display:inline;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$dp['id'] ?>">
                <input type="hidden" name="note" value="">
                <button class="btn" style="padding:4px 10px;">Approve</button>
              </form>
              <form method="post" action="<?= base_url('admin/due-payments/reject') ?>" style="display:inline; margin-left:4px;">
                <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
                <input type="hidden" name="id" value="<?= (int)$dp['id'] ?>">
                <input type="text" name="note" placeholder="Reason" maxlength="200" required style="width:120px;">
                <button class="btn" style="padding:4px 10px; border-color:#dc2626; color:#dc2626;">Reject</button>
              </form>
            <?php else: ?>
              <div style="font-size:12px;">
                <?= htmlspecialchars($dp['reviewer_name'] ?? '') ?> <?= htmlspecialchars($dp['reviewed_at'] ?? '') ?>
              </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<?php if (isset($boardSummary) && is_array($boardSummary)): ?>
<br>
<div class="card" style="margin-top:12px">
  <h3 style="margin-top:0; text-align:center;">Sales by Exam Board (Approved only)</h3>
  <table id="adminBoards">
    <thead>
      <tr>
        <th>Exam Board</th>
        <th>Approved Sales</th>
        <th>Paid</th>
        <th>Due</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($boardSummary as $board => $agg): ?>
        <tr>
          <td><?= htmlspecialchars($board) ?></td>
          <td><?= (int)$agg['cnt'] ?></td>
          <td><?= number_format((int)$agg['paid']) ?></td>
          <td><?= number_format((int)$agg['due']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th style="text-align:right">Totals:</th>
        <th><?= isset($boardTotals) ? (int)$boardTotals['cnt'] : 0 ?></th>
        <th><?= isset($boardTotals) ? number_format((int)$boardTotals['paid']) : '0' ?></th>
        <th><?= isset($boardTotals) ? number_format((int)$boardTotals['due'])  : '0' ?></th>
      </tr>
    </tfoot>
  </table>
  <p class="help">Shown only on the main dashboard (no status filter). Counts and amounts include approved records from the current month.</p>
</div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const table = document.getElementById('adminSales');
  if (table) {
    const tbody = table.tBodies[0];
    const headers = Array.from(table.querySelectorAll('th'));
    headers.forEach((th, idx)=>{
      th.style.cursor = 'pointer';
      th.addEventListener('click', ()=>{
        const asc = th.dataset.sort !== 'asc';
        headers.forEach(h=>h.dataset.sort='');
        th.dataset.sort = asc ? 'asc' : 'desc';
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isNum = rows.every(r => !isNaN(parseFloat((r.cells[idx]?.textContent || '').replace(/[^0-9.-]/g,''))));
        rows.sort((a,b)=>{
          const va = (a.cells[idx]?.textContent || '').trim();
          const vb = (b.cells[idx]?.textContent || '').trim();
          if (isNum) {
            return (parseFloat(va.replace(/[^0-9.-]/g,'')) || 0) - (parseFloat(vb.replace(/[^0-9.-]/g,'')) || 0);
          }
          return va.localeCompare(vb);
        });
        if (!asc) rows.reverse();
        rows.forEach(r=>tbody.appendChild(r));
      });
    });
  }
});
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
  // find the KPI label "Profit"
  const label = Array.from(document.querySelectorAll('.card div'))
    .find(el => el.textContent.trim() === 'Profit');
  if (!label) return;

  const valueEl = label.nextElementSibling; // the big number just after the label
  if (!valueEl) return;

  const raw = valueEl.textContent.trim().replace(/,/g, '');
  const num = Number(raw);

  if (num < 0) {
    label.textContent = 'Loss';
    valueEl.textContent = raw.replace('-', ''); // show absolute value
    valueEl.style.color = '#b91c1c'; // red
  }
});
</script>

<div id="noteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:9999; align-items:center; justify-content:center; padding:16px;">
  <div style="background:#fff; color:#111; min-width:280px; max-width:520px; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.2); padding:16px; position:relative;">
    <button type="button" id="noteModalClose" style="position:absolute; top:8px; right:8px; border:none; background:transparent; font-size:16px; cursor:pointer;">×</button>
    <h4 style="margin-top:0; margin-bottom:8px;">Note</h4>
    <div id="noteModalBody" style="white-space:pre-wrap; line-height:1.4;"></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('noteModal');
  const bodyEl = document.getElementById('noteModalBody');
  const closeBtn = document.getElementById('noteModalClose');
  if (modal && bodyEl && closeBtn) {
    function open(note){
      bodyEl.textContent = note || '—';
      modal.style.display = 'flex';
    }
    function close(){
      modal.style.display = 'none';
      bodyEl.textContent = '';
    }
    document.querySelectorAll('.view-note').forEach(a=>{
      a.addEventListener('click', function(e){
        e.preventDefault();
        open(this.dataset.note || '');
      });
    });
    closeBtn.addEventListener('click', close);
    modal.addEventListener('click', function(e){
      if (e.target === modal) close();
    });
  }
});
</script>

<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>


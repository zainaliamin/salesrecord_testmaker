<?php
$title='My Submissions';
$rejectionNotes = $rejectionNotes ?? [];
$myDuePayments  = $myDuePayments  ?? [];
ob_start();
?>
<h2>My Submissions</h2>
<p><a href="<?= base_url('agent/sales/create') ?>">+ Add New Submission</a></p>

<?php
// Totals should include ONLY approved entries (rows shown below include all statuses)
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



<div class="card" style="padding:8px;margin:8px 0; display:flex; gap:12px; flex-wrap:wrap;">
  <div style="flex:1; min-width:220px;">
    <strong>My Received (this month):</strong> <?= number_format($myIncomeThisMonth ?? 0) ?>
  </div>
  <div style="flex:1; min-width:220px;">
    <strong>My Commission (this month):</strong> <?= number_format($myCommissionThisMonth ?? 0) ?>
  </div>
</div>


<div class="table-wrap" style="overflow-x:auto;">
  <table id="agentSubmissions" class="sortable" style="min-width: 1100px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Full Name</th>
        <th>Phone</th>
        <th>City</th>
        <th>School</th>
        <th>Module</th>
        <th>Package Duration</th>

        <th>Payable</th>
        <th>Paid</th>
        <th>Due</th>
        <th>Commission</th>
        <th>Start / End</th>
        <th>Note</th>
        <th>Status</th>
        <th>Rejection Reason</th>
        <th>Created</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
    <?php foreach($sales as $s): ?>
      <tr>
        <td>#<?= (int)$s['id'] ?></td>
        <td><?= htmlspecialchars($s['full_name']) ?></td>
        <td><?= htmlspecialchars($s['phone']) ?></td>
        <td><?= htmlspecialchars($s['city']) ?></td>
        <td><?= htmlspecialchars($s['school_name']) ?></td>
        <td><?= htmlspecialchars($s['module_name']) ?></td>
        <td><?= htmlspecialchars($s['package_duration'] ?? '-') ?></td>

        <td><?= number_format((int)$s['amount_to_be_paid']) ?></td>
        <td><?= number_format((int)$s['amount_paid']) ?></td>
        <td><?= number_format((int)$s['amount_due']) ?></td>
        <td><?= number_format((int)$s['commission_amount']) ?></td>
        <td><?= htmlspecialchars($s['package_start_date']) ?> / <?= htmlspecialchars($s['package_end_date']) ?></td>
        <td>
          <?php if (($s['agent_note'] ?? '') !== ''): ?>
            <a href="#" class="view-note" data-note="<?= htmlspecialchars($s['agent_note'], ENT_QUOTES) ?>">View Note</a>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </td>
        <td><span class="badge <?= $s['status'] ?>"><?= $s['status'] ?></span></td>
        <td>
          <?php if (($s['status'] ?? '') === 'rejected' && isset($rejectionNotes[(int)$s['id']])): ?>
            <a href="#" class="view-note" data-note="<?= htmlspecialchars($rejectionNotes[(int)$s['id']], ENT_QUOTES) ?>">View Reason</a>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($s['created_at']) ?></td>
        <td>
          <?php if (($s['status'] ?? '') === 'rejected'): ?>
            <a href="<?= base_url('agent/sales/edit?id='.(int)$s['id']) ?>">Edit &amp; Resubmit</a>
          <?php else: ?>
            &mdash;
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="6" style="text-align:right">Totals (Approved only):</th>

        <th><?= number_format($tToPay) ?></th>
        <th><?= number_format($tPaid) ?></th>
        <th><?= number_format($tDue) ?></th>
        <th><?= number_format($tComm) ?></th>
        <th colspan="5"></th>
      </tr>
    </tfoot>
  </table>
</div>
<?php if (!empty($myDuePayments)): ?>
<div class="card" style="margin-top:16px;">
  <h3 style="margin-top:0;">My Due Payment Requests</h3>
  <div class="table-wrap" style="overflow-x:auto;">
  <table style="min-width: 900px;">
    <thead>
      <tr>
        <th>ID</th>
        <th>Sale</th>
        <th>Payable (at request)</th>
          <th>Paid</th>
          <th>Remaining</th>
          <th>Method</th>
          <th>Next Payment Date</th>
          <th>Status</th>
          <th>Admin Note</th>
          <th>Receipt</th>
        <th>Submitted</th>
        <th>Actions</th>
      </tr>
    </thead>
      <tbody>
        <?php foreach ($myDuePayments as $dp): ?>
          <?php
            $paidAmt = (int)($dp['amount'] ?? 0);
            $payableSnap = isset($dp['payable_at_request']) ? (int)$dp['payable_at_request'] : 0;
            $remainingSnap = isset($dp['remaining_at_request']) ? (int)$dp['remaining_at_request'] : max($payableSnap - $paidAmt, 0);
          ?>
          <tr>
            <td>#<?= (int)$dp['id'] ?></td>
            <td>
              <?= htmlspecialchars($dp['school_name'] ?? $dp['sale_school'] ?? '') ?><br>
              <?= htmlspecialchars($dp['phone'] ?? $dp['sale_phone'] ?? '') ?>
            </td>
            <td><?= number_format($payableSnap) ?></td>
            <td><?= number_format($paidAmt) ?></td>
          <td><?= number_format($remainingSnap) ?></td>
          <td><?= htmlspecialchars($dp['method']) ?></td>
          <td><?= htmlspecialchars($dp['next_payment_date'] ?? '—') ?></td>
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
              &mdash;
            <?php endif; ?>
          </td>
            <td>
            <?php if (!empty($dp['receipt_path'])): ?>
              <a href="<?= base_url('public/proofs/'.rawurlencode($dp['receipt_path'])) ?>" target="_blank">View</a>
            <?php else: ?>
              &mdash;
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($dp['created_at']) ?></td>
          <td>
            <?php if (($dp['status'] ?? '') === 'rejected'): ?>
              <a href="<?= base_url('agent/due/edit?id='.(int)$dp['id']) ?>">Edit &amp; Resubmit</a>
            <?php else: ?>
              &mdash;
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  const table = document.getElementById('agentSubmissions');
  if (!table) return;
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
})();
</script>

<div id="noteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.35); z-index:9999; align-items:center; justify-content:center; padding:16px;">
  <div style="background:#fff; color:#111; min-width:280px; max-width:520px; border-radius:10px; box-shadow:0 10px 30px rgba(0,0,0,.2); padding:16px; position:relative;">
    <button type="button" id="noteModalClose" style="position:absolute; top:8px; right:8px; border:none; background:transparent; font-size:16px; cursor:pointer;">×</button>
    <h4 style="margin-top:0; margin-bottom:8px;">Agent Note</h4>
    <div id="noteModalBody" style="white-space:pre-wrap; line-height:1.4;"></div>
  </div>
</div>

<script>
(function(){
  const modal = document.getElementById('noteModal');
  const bodyEl = document.getElementById('noteModalBody');
  const closeBtn = document.getElementById('noteModalClose');
  if (!modal || !bodyEl || !closeBtn) return;

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
})();
</script>

<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

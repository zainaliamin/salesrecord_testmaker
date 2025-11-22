<?php $title='Payments Ledger'; ob_start(); ?>
<h2>Payments Ledger</h2>

<form method="get" action="<?= base_url('admin/payments') ?>" class="card" style="padding:10px; margin:8px 0;">
  <div style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
    <div>
      <label>From (YYYY-MM-DD)</label>
      <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>">
    </div>
    <div>
      <label>To (exclusive)</label>
      <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>">
    </div>
    <div style="flex:1; min-width:200px;">
      <label>Search (school or number)</label>
      <input type="text" name="search" value="<?= htmlspecialchars($searchTerm ?? '') ?>" placeholder="e.g. Beaconhouse or 03001234567">
    </div>
    <div>
      <button type="submit">Apply</button>
      <a class="btn" href="<?= base_url('admin/payments') ?>">This Month</a>
      <a class="btn" href="<?= base_url('admin/sales') ?>">Back to Dashboard</a>
    </div>
  </div>
  <div style="margin-top:8px; font-size:12px; opacity:0.75;">
    Tip: The "To" date is exclusive. For a full month, set From = 1st and To = 1st of next month.
  </div>
</form>

<?php if (!empty($searchTerm)): ?>
  <div class="card" style="margin:8px 0; font-size:14px;">
    Filtering for <strong>"<?= htmlspecialchars($searchTerm) ?>"</strong> in school name or phone.
    <a class="btn" style="margin-left:12px;" href="<?= base_url('admin/payments?date_from='.rawurlencode($date_from).'&date_to='.rawurlencode($date_to)) ?>">Clear Search</a>
  </div>
<?php endif; ?>

<?php $monthLabel = date('F', strtotime($date_from)); ?>
<div class="card" style="display:flex; gap:16px; flex-wrap:wrap; margin:8px 0;">
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Total Received</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format($totalAmount ?? 0) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">New Sales</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format($bySource['new'] ?? 0) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Renewals</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format($bySource['renewal'] ?? 0) ?></div>
  </div>
  <div style="flex:1; min-width:200px;">
    <div style="font-size:14px; opacity:0.7;">Due Payments</div>
    <div style="font-size:22px; font-weight:700;"><?= number_format($bySource['due'] ?? 0) ?></div>
  </div>
</div>

<table>
  <thead>
    <tr>
      <th>ID</th>
      <th>Paid At</th>
      <th>Sale</th>
      <th>School</th>
      <th>Phone</th>
      <th>Amount</th>
      <th>Method</th>
      <th>Source</th>
      <th>Agent</th>
      <th>Receipt</th>
    </tr>
  </thead>
  <tbody>
    <?php if (!empty($payments)): ?>
      <?php foreach($payments as $p): ?>
        <tr>
          <td>#<?= (int)$p['id'] ?></td>
          <td><?= htmlspecialchars($p['paid_at']) ?></td>
          <td>
            <?php if (!empty($p['sale_id'])): ?>
              <a href="<?= base_url('admin/sales/show?id='.(int)$p['sale_id']) ?>">Sale #<?= (int)$p['sale_id'] ?></a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($p['school_name']) ?></td>
          <td><?= htmlspecialchars($p['phone']) ?></td>
          <td><?= number_format((int)$p['amount']) ?></td>
          <td><?= htmlspecialchars($p['method']) ?></td>
          <td><span class="badge"><?= htmlspecialchars($p['source'] ?? 'new') ?></span></td>
          <td><?= htmlspecialchars($p['agent_name'] ?? '') ?></td>
          <td>
            <?php
              // Accept either column name: proof_file (recommended) or receipt_path (legacy)
              $proof = $p['proof_file'] ?? ($p['receipt_path'] ?? '');
              if (!empty($proof)):
                $href = base_url('public/proofs/'.rawurlencode($proof));
            ?>
              <a target="_blank" href="<?= htmlspecialchars($href) ?>">View</a>
            <?php else: ?>
              —
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php else: ?>
      <tr><td colspan="10" style="text-align:center;">No payments in this range.</td></tr>
    <?php endif; ?>
  </tbody>
</table>

<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

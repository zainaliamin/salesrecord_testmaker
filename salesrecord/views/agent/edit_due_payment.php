<?php
$title = 'Edit Due Payment';
ob_start();
?>
<div class="card">
  <h2>Edit Due Payment #<?= (int)$dp['id'] ?></h2>
  <p>
    School: <?= htmlspecialchars($dp['school_name'] ?? $dp['sale_school'] ?? '') ?><br>
    Phone: <?= htmlspecialchars($dp['phone'] ?? $dp['sale_phone'] ?? '') ?><br>
    Current Due: <?= number_format((int)($dp['sale_due'] ?? 0)) ?>
  </p>

  <form method="post" action="<?= base_url('agent/due/update') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$dp['id'] ?>">

    <div class="grid">
      <div>
        <label>Amount</label>
        <input type="number" name="amount" min="1" value="<?= (int)$dp['amount'] ?>" required>
      </div>
      <div>
        <label>Method</label>
        <select name="method" required>
          <?php foreach (['bank_transfer','easypaisa','jazzcash','cash','other'] as $m): ?>
            <option value="<?= $m ?>" <?= ($dp['method'] ?? '') === $m ? 'selected' : '' ?>><?= ucfirst(str_replace('_',' ',$m)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Next Payment Date (required if remaining)</label>
        <input type="date" name="next_payment_date" value="<?= htmlspecialchars($dp['next_payment_date'] ?? '') ?>">
      </div>
      <div style="grid-column:1 / -1">
        <label>Agent Note (optional)</label>
        <input type="text" name="agent_note" maxlength="250" value="<?= htmlspecialchars($dp['agent_note'] ?? '') ?>">
      </div>
      <div style="grid-column:1 / -1">
        <label>Replace Receipt (jpg/png <=3MB)</label>
        <input type="file" name="receipt" accept=".jpg,.jpeg,.png">
        <?php if (!empty($dp['receipt_path'])): ?>
          <div class="help">Current: <?= htmlspecialchars($dp['receipt_path']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <button type="submit">Update &amp; Resubmit</button>
    <a class="btn" href="<?= base_url('agent/dashboard') ?>" style="margin-left:8px;">Cancel</a>
  </form>
</div>

<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

<?php $title='Admin • Edit Sale #'.$sale['id']; ob_start(); ?>
<div class="card">
  <h2>Edit Sale #<?= (int)$sale['id'] ?></h2>
  <p><b>Agent:</b> <?= htmlspecialchars($sale['agent_user_name']) ?> (<?= htmlspecialchars($sale['agent_user_email']) ?>)</p>

  <form method="post" action="<?= base_url('admin/sales/update') ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">

    <div class="grid">
      <div><label>Full Name</label><input name="full_name" value="<?= htmlspecialchars($sale['full_name']) ?>" required></div>
      <div><label>Phone (11 digits)</label><input name="phone" value="<?= htmlspecialchars($sale['phone']) ?>" maxlength="11" required></div>

      <div><label>City</label><input name="city" value="<?= htmlspecialchars($sale['city']) ?>" required></div>
      <div><label>School Name</label><input name="school_name" value="<?= htmlspecialchars($sale['school_name']) ?>" required></div>

      <div><label>Module Name</label><input name="module_name" value="<?= htmlspecialchars($sale['module_name']) ?>" required></div>
      <div><label>Package Duration</label><input name="package_duration" value="<?= htmlspecialchars($sale['package_duration'] ?? '') ?>"></div>

      <div>
        <label>Customer Type</label>
        <select name="customer_type" required>
          <option value="new" <?= $sale['customer_type']==='new'?'selected':'' ?>>New</option>
          <option value="old" <?= $sale['customer_type']==='old'?'selected':'' ?>>Old</option>
        </select>
      </div>

      <div><label>Package Start Date</label><input type="date" name="package_start_date" value="<?= htmlspecialchars($sale['package_start_date']) ?>" required></div>
      <div><label>Package End Date</label><input type="date" name="package_end_date" value="<?= htmlspecialchars($sale['package_end_date']) ?>" required></div>

      <div><label>Amount To Be Paid</label><input type="number" min="0" name="amount_to_be_paid" value="<?= (int)$sale['amount_to_be_paid'] ?>" required></div>
      <div><label>Amount Paid</label><input type="number" min="0" name="amount_paid" value="<?= (int)$sale['amount_paid'] ?>" required></div>

      <div>
        <label>Payment Method</label>
        <select name="payment_method" required>
          <?php foreach (['bank_transfer','easypaisa','jazzcash','cash','other'] as $pm): ?>
            <option value="<?= $pm ?>" <?= $sale['payment_method']===$pm?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$pm)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div><label>Commission Amount (PKR)</label><input type="number" min="0" name="commission_amount" value="<?= (int)$sale['commission_amount'] ?>" required></div>

      <div>
        <label>Sale Source</label>
        <select name="sale_source" required>
          <?php foreach (['Referral','Ad boost','Manual','Old Customer','Sales Officer','Add classes'] as $src): ?>
            <option <?= $sale['sale_source']===$src?'selected':'' ?>><?= $src ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
        <label>Province</label>
        <select name="province" required>
          <?php foreach (['Punjab','AJK','Federal'] as $pv): ?>
            <option <?= $sale['province']===$pv?'selected':'' ?>><?= $pv ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div>
  <label>Exam Board</label>
  <select name="exam_board" required>
    <?php foreach ($boards as $eb): ?>
      <option value="<?= htmlspecialchars($eb) ?>" <?= $sale['exam_board']===$eb?'selected':'' ?>><?= htmlspecialchars($eb) ?></option>
    <?php endforeach; ?>
  </select>
</div>

      <div style="grid-column:1 / -1">
        <label>Agent Note (optional)</label>
        <textarea name="agent_note" rows="2" placeholder="e.g., Renewal context or relationship details"><?= htmlspecialchars($sale['agent_note'] ?? '') ?></textarea>
      </div>

      <div>
        <label>Next Payment Date</label>
        <input type="date" name="next_payment_date" value="<?= htmlspecialchars($sale['next_payment_date'] ?? '') ?>">
        <div class="help">Required if Amount Due &gt; 0</div>
      </div>

      <div>
        <label>Status</label>
        <select name="status" required>
          <option value="pending"  <?= $sale['status']==='pending'?'selected':''  ?>>Pending</option>
          <option value="approved" <?= $sale['status']==='approved'?'selected':'' ?>>Approved</option>
          <option value="rejected" <?= $sale['status']==='rejected'?'selected':'' ?>>Rejected</option>
        </select>
      </div>

      <div>
        <label>Replace Receipt (jpg/png ≤3MB)</label>
        <input type="file" name="receipt_image" accept=".jpg,.jpeg,.png">
        <?php if (!empty($sale['receipt_image_path'])): ?>
          <div class="help">Current: <?= htmlspecialchars($sale['receipt_image_path']) ?></div>
          <img src="<?= base_url('public/proofs/'.$sale['receipt_image_path']) ?>" alt="Receipt" style="max-width:360px;border:1px solid #eee;border-radius:8px;margin-top:6px">
        <?php endif; ?>
      </div>
    </div>

    <button type="submit">Save Changes</button>
    <a href="<?= base_url('admin/sales') ?>" style="margin-left:8px">Cancel</a>
  </form>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

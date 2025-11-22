<?php $title='Admin • Edit Expense #'.$expense['id']; ob_start(); ?>
<div class="card">
  <h2>Edit Expense #<?= (int)$expense['id'] ?></h2>
  <p class="help">Created at: <?= htmlspecialchars($expense['created_at']) ?></p>

  <form method="post" action="<?= base_url('admin/expenses/update') ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$expense['id'] ?>">

    <div class="grid">
      <div>
        <label>Expense Name</label>
        <input name="name" value="<?= htmlspecialchars($expense['name']) ?>" required>
      </div>
      <div>
        <label>Amount (PKR)</label>
        <input type="number" name="amount" min="1" value="<?= (int)$expense['amount'] ?>" required>
      </div>
    </div>

    <button type="submit">Save Changes</button>
    <a class="btn" href="<?= base_url('admin/expenses') ?>" style="margin-left:8px">Cancel</a>
  </form>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

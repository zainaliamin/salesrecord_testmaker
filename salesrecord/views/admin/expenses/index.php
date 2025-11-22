<?php $title='Admin • Expenses (This Month)'; ob_start(); ?>
<h2>Expenses (This Month)</h2>

<div class="card" style="margin-bottom:12px">
  <h3 style="margin-top:0">Add Expense</h3>
  <form method="post" action="<?= base_url('admin/expenses/store') ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <div class="grid">
      <div>
        <label>Expense Name</label>
        <input name="name" placeholder="e.g., Facebook Ads" required>
      </div>
      <div>
        <label>Amount (PKR)</label>
        <input type="number" name="amount" min="1" required>
      </div>
    </div>
    <button type="submit">Add</button>
  </form>
</div>

<div class="card">
  <h3 style="margin-top:0">This Month’s Expenses</h3>
  <table>
    <thead>
      <tr><th>ID</th><th>Name</th><th>Amount</th><th>Created</th><th>Action</th></tr>
    </thead>
    <tbody>
      <?php foreach($rows as $r): ?>
        <tr>
          <td>#<?= (int)$r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= number_format((int)$r['amount']) ?></td>
          <td><?= htmlspecialchars($r['created_at']) ?></td>
          <td>
            <a href="<?= base_url('admin/expenses/edit?id='.(int)$r['id']) ?>">Edit</a>
            &nbsp;|&nbsp;
            <form method="post" action="<?= base_url('admin/expenses/delete') ?>" style="display:inline" onsubmit="return confirm('Delete this expense?');">
              <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button type="submit">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <th colspan="2" style="text-align:right">Total (This Month):</th>
        <th><?= number_format($total) ?></th>
        <th colspan="2"></th>
      </tr>
    </tfoot>
  </table>
</div>

<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

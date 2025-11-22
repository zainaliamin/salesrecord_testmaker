<?php $title='Admin • Create Agent'; ob_start(); ?>
<div class="card">
  <h2>Create Agent</h2>
  <form method="post" action="<?= base_url('admin/users/store') ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Full Name</label>
    <input name="name" required>
    <label>Email</label>
    <input type="email" name="email" required>
    <div class="grid">
      <div>
        <label>Password</label>
        <input type="password" name="password" required>
      </div>
      <div>
        <label>Confirm Password</label>
        <input type="password" name="password_confirm" required>
      </div>
    </div>
    <button type="submit">Create Agent</button>
  </form>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

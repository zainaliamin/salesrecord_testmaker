<?php $title='Admin • Change Password'; ob_start(); ?>
<div class="card">
  <h2>Change Password</h2>
  <form method="post" action="<?= base_url('admin/password') ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Current Password</label>
    <input type="password" name="current_password" required>
    <div class="grid">
      <div>
        <label>New Password</label>
        <input type="password" name="new_password" required>
      </div>
      <div>
        <label>Confirm New Password</label>
        <input type="password" name="new_password_confirm" required>
      </div>
    </div>
    <button type="submit">Update Password</button>
  </form>
  <p class="help">After changing password, you'll be logged out and must log in again.</p>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

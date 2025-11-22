<?php $title='Login'; ob_start(); ?>
<div class="card">
  <h2>Login</h2>
  <form method="post" action="<?= base_url('login') ?>">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <label>Email</label><input type="email" name="email" required>
    <label>Password</label><input type="password" name="password" required>
    <button type="submit">Login</button>
  </form>
  <?php if(!empty($error)) echo "<p style='color:#b91c1c;margin-top:8px'>".htmlspecialchars($error)."</p>"; ?>
</div>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

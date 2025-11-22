<?php $title='Admin • Add Exam Board'; ob_start(); ?>
<h2>Add Exam Board</h2>

<form method="post" action="<?= base_url('admin/boards/store') ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <label>Board Name</label>
  <input name="name" maxlength="64" placeholder="e.g., PTB + Federal" required>
  <button type="submit">Add</button>
  <a href="<?= base_url('admin/boards') ?>" style="margin-left:8px">Cancel</a>
</form>

<p class="help">Name must be unique. New boards will appear in the “Exam Board” dropdown in Agent and Admin forms.</p>

<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

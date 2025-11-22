<?php $title='Admin • Edit Exam Board'; ob_start(); ?>
<h2>Edit Exam Board</h2>

<form method="post" action="<?= base_url('admin/boards/update') ?>">
  <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
  <input type="hidden" name="id" value="<?= (int)$board['id'] ?>">

  <label>Board Name</label>
  <input name="name" value="<?= htmlspecialchars($board['name']) ?>" maxlength="64" required>

  <button type="submit">Save</button>
  <a href="<?= base_url('admin/boards') ?>" style="margin-left:8px">Cancel</a>
</form>

<p class="help">
  Renaming will also update all existing sales that use this board.
</p>

<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

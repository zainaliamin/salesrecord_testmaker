<?php $title='Admin • Exam Boards'; ob_start(); ?>
<h2>Exam Boards</h2>
<p><a href="<?= base_url('admin/boards/create') ?>">+ Add New Board</a></p>

<table>
  <thead>
    <tr><th>ID</th><th>Name</th><th>Used (this month)</th><th>Action</th></tr>
  </thead>
  <tbody>
    <?php foreach($boards as $b): ?>
      <tr>
        <td>#<?= (int)$b['id'] ?></td>
        <td><?= htmlspecialchars($b['name']) ?></td>
        <td><?= (int)($usageMap[$b['name']] ?? 0) ?></td>
       


        <td>
  <a href="<?= base_url('admin/boards/edit?id='.$b['id']) ?>">Edit</a>
  &nbsp;|&nbsp;
  <form method="post" action="<?= base_url('admin/boards/delete') ?>" onsubmit="return confirm('Delete this board?');" style="display:inline">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
    <button type="submit">Delete</button>
  </form>
</td>

      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

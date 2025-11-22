<?php $title='Admin • Users'; ob_start(); ?>
<h2>Users</h2>
<p><a href="<?= base_url('admin/users/create') ?>">+ Create Agent</a></p>
<table>
  <thead><tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Password</th><th>Role</th><th>Created</th>
  </tr></thead>
  <tbody>
  <?php foreach($users as $u): ?>
    <tr>
      <td>#<?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars($u['name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
  <td><?= htmlspecialchars($u['password']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td><?= htmlspecialchars($u['created_at']) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

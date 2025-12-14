<?php $title='Admin • Users'; ob_start(); ?>
<h2>Users</h2>
<p><a href="<?= base_url('admin/users/create') ?>">+ Create Agent</a></p>
<table>
  <thead><tr>
    <th>ID</th><th>Name</th><th>Email</th><th>Password</th><th>Role</th><th>Status</th><th>Created</th><th>Actions</th>
  </tr></thead>
  <tbody>
  <?php foreach($users as $u): ?>
    <tr>
      <td>#<?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars($u['name']) ?></td>
      <td><?= htmlspecialchars($u['email']) ?></td>
  <td><?= htmlspecialchars($u['password']) ?></td>
      <td><?= htmlspecialchars($u['role']) ?></td>
      <td>
        <span class="badge <?= htmlspecialchars($u['status'] ?? 'active') ?>"><?= htmlspecialchars($u['status'] ?? 'active') ?></span>
      </td>
      <td><?= htmlspecialchars($u['created_at']) ?></td>
      <td>
        <?php if (($u['role'] ?? '') !== 'admin'): ?>
          <?php $isActive = ($u['status'] ?? 'active') === 'active'; ?>
          <form method="post" action="<?= base_url('admin/users/status') ?>" style="display:inline;">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
            <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
            <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
            <button type="submit" class="btn" style="padding:4px 8px; <?= $isActive ? 'border-color:#dc2626;color:#dc2626;' : '' ?>">
              <?= $isActive ? 'Mark Inactive' : 'Mark Active' ?>
            </button>
          </form>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

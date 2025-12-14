<?php $title='Admin › Users'; ob_start(); ?>
<style>
  .user-actions form {
    display: inline-block;
    margin: 2px 4px 2px 0;
  }
  .btn-pill {
    border: 1px solid #d1d5db;
    background: #fff;
    color: #111;
    padding: 6px 12px;
    border-radius: 999px;
    font-weight: 600;
    letter-spacing: .01em;
    box-shadow: 0 6px 16px rgba(0,0,0,.05);
    transition: all .18s ease;
    cursor: pointer;
  }
  .btn-pill:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(0,0,0,.08);
  }
  .btn-pill.danger {
    border-color: #ef4444;
    color: #b91c1c;
    background: #fff5f5;
  }
  .btn-pill.success {
    border-color: #10b981;
    color: #065f46;
    background: #ecfdf3;
  }
</style>
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
          <div class="user-actions">
            <form method="post" action="<?= base_url('admin/users/status') ?>">
              <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <input type="hidden" name="status" value="<?= $isActive ? 'inactive' : 'active' ?>">
              <button type="submit" class="btn-pill <?= $isActive ? 'danger' : 'success' ?>">
                <?= $isActive ? 'Mark Inactive' : 'Mark Active' ?>
              </button>
            </form>
          </div>
        <?php else: ?>
          —
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php $content=ob_get_clean(); require __DIR__.'/../../layouts/base.php'; ?>

<?php $pageTitle = 'Staff Management'; ?>

<?php if (\App\Core\Session::hasFlash('success')): ?>
  <div class="alert alert-success" data-dismiss style="margin-bottom: 1.5rem;">
    <i class="fas fa-check-circle" style="flex-shrink:0"></i>
    <div><?= \App\Core\Session::getFlash('success') ?></div>
  </div>
<?php endif; ?>

<?php if (\App\Core\Session::hasFlash('error')): ?>
  <div class="alert alert-danger" data-dismiss style="margin-bottom: 1.5rem;">
    <i class="fas fa-exclamation-circle" style="flex-shrink:0"></i>
    <div><?= \App\Core\Session::getFlash('error') ?></div>
  </div>
<?php endif; ?>

<?php if (\App\Core\Session::hasFlash('errors')): ?>
  <div class="alert alert-danger" data-dismiss style="margin-bottom: 1.5rem;">
    <i class="fas fa-exclamation-circle" style="flex-shrink:0"></i>
    <ul style="margin:0;padding-left:1rem">
      <?php foreach (\App\Core\Session::getFlash('errors', []) as $err): ?>
        <li><?= e($err) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.5rem">
  <div>
    <h2 style="margin:0;font-size:1.25rem;font-weight:700">Staff Management</h2>
    <p style="margin:.2rem 0 0;color:var(--text-muted);font-size:.875rem"><?= count($users) ?> registered users</p>
  </div>
</div>

<div class="card">
  <div style="overflow-x:auto">
    <table class="data-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Name</th>
          <th>Username</th>
          <th>Department</th>
          <th>Role</th>
          <th>Contact Info</th>
          <th>Joined</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($users as $u): ?>
          <tr>
            <td style="color:var(--text-muted)"><?= $u['id'] ?></td>
            <td style="font-weight:600">
              <?= e($u['full_name'] ?: $u['username']) ?>
            </td>
            <td style="color:var(--text-muted)"><?= e($u['username']) ?></td>
            <td><?= e($u['department']) ?></td>
            <td>
              <span class="badge-role <?= e($u['role']) ?>"><?= ucfirst(e($u['role'])) ?></span>
            </td>
            <td style="font-size:.825rem;line-height:1.4">
              <?php if (!empty($u['email'])): ?>
                <div><i class="fas fa-envelope" style="color:var(--text-muted);width:14px"></i> <?= e($u['email']) ?></div>
              <?php endif; ?>
              <?php if (!empty($u['phone'])): ?>
                <div><i class="fas fa-phone" style="color:var(--text-muted);width:14px"></i> <?= e($u['phone']) ?></div>
              <?php endif; ?>
              <?php if (empty($u['email']) && empty($u['phone'])): ?>
                <span style="color:var(--text-muted);font-style:italic">None</span>
              <?php endif; ?>
            </td>
            <td style="color:var(--text-muted);font-size:.825rem"><?= format_date($u['created_at']) ?></td>
            <td>
              <div style="display:flex;gap:.4rem">
                <!-- Edit button -->
                <button type="button" class="btn btn-ghost btn-sm" title="Edit user"
                        onclick='openEditUserModal(<?= json_encode($u, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                  <i class="fas fa-edit"></i>
                </button>
                <!-- Reset password -->
                <form method="POST" action="<?= url('admin/users/reset') ?>"
                      onsubmit="return confirm('Reset password for <?= e($u['username']) ?>?')">
                  <?= csrf_field() ?>
                  <input type="hidden" name="id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn btn-ghost btn-sm" title="Reset password">
                    <i class="fas fa-key"></i>
                  </button>
                </form>
                <!-- Delete -->
                <?php if ((int)$u['id'] !== \App\Core\Auth::id()): ?>
                  <form method="POST" action="<?= url('admin/users/delete') ?>"
                        onsubmit="return confirm('Delete user <?= e($u['username']) ?>? All their logs will be removed.')">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= $u['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)" title="Delete user">
                      <i class="fas fa-trash"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Edit User Modal -->
<div id="editUserModalOverlay" class="sidebar-overlay" style="z-index:300;display:none" onclick="closeEditUserModal()"></div>
<div id="editUserModal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:14px;padding:1.5rem;width:100%;max-width:520px;z-index:400;box-shadow:0 20px 60px rgba(0,0,0,.2)">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem;border-bottom:1px solid var(--border);padding-bottom:0.75rem">
    <h3 style="margin:0;font-size:1.1rem;font-weight:600"><i class="fas fa-user-edit" style="color:var(--primary);margin-right:.5rem"></i>Edit User Details</h3>
    <button onclick="closeEditUserModal()" style="background:none;border:none;cursor:pointer;font-size:1.35rem;color:var(--text-muted);padding:0;line-height:1">&times;</button>
  </div>
  <form method="POST" action="<?= url('admin/users/update') ?>">
    <?= csrf_field() ?>
    <input type="hidden" name="id" id="edit-user-id">

    <div class="form-grid-2" style="margin-bottom:1rem">
      <div>
        <label class="form-label" for="edit-user-username">Username</label>
        <input type="text" name="username" id="edit-user-username" class="form-control" required>
      </div>
      <div>
        <label class="form-label" for="edit-user-fullname">Full Name</label>
        <input type="text" name="full_name" id="edit-user-fullname" class="form-control" required>
      </div>
    </div>

    <div class="form-grid-2" style="margin-bottom:1rem">
      <div>
        <label class="form-label" for="edit-user-email">Email Address</label>
        <input type="email" name="email" id="edit-user-email" class="form-control" placeholder="user@email.com">
      </div>
      <div>
        <label class="form-label" for="edit-user-phone">Phone Number</label>
        <input type="text" name="phone" id="edit-user-phone" class="form-control" placeholder="+260 9X XXX XXXX">
      </div>
    </div>

    <div class="form-grid-2" style="margin-bottom:1.5rem">
      <div>
        <label class="form-label" for="edit-user-department">Department</label>
        <select name="department" id="edit-user-department" class="form-select">
          <option value="General">General Operations</option>
          <option value="News">News & Editorial</option>
          <option value="Technical">Technical Support</option>
          <option value="Finance">Finance & Accounting</option>
          <option value="HR">Human Resources</option>
        </select>
      </div>
      <div>
        <label class="form-label" for="edit-user-role">Role</label>
        <select name="role" id="edit-user-role" class="form-select">
          <option value="employee">Employee</option>
          <option value="supervisor">Supervisor</option>
          <option value="admin">Administrator</option>
        </select>
      </div>
    </div>

    <div style="display:flex;gap:.75rem;justify-content:flex-end;border-top:1px solid var(--border);padding-top:1rem">
      <button type="button" class="btn btn-ghost btn-sm" onclick="closeEditUserModal()">Cancel</button>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save Changes</button>
    </div>
  </form>
</div>

<style>
  #editUserModal.open { display:block !important; }
</style>

<script>
function openEditUserModal(user) {
  document.getElementById('edit-user-id').value = user.id;
  document.getElementById('edit-user-username').value = user.username;
  document.getElementById('edit-user-fullname').value = user.full_name || '';
  document.getElementById('edit-user-email').value = user.email || '';
  document.getElementById('edit-user-phone').value = user.phone || '';
  document.getElementById('edit-user-department').value = user.department || 'General';
  document.getElementById('edit-user-role').value = user.role || 'employee';

  document.getElementById('editUserModal').classList.add('open');
  document.getElementById('editUserModalOverlay').style.display = 'block';
}

function closeEditUserModal() {
  document.getElementById('editUserModal').classList.remove('open');
  document.getElementById('editUserModalOverlay').style.display = 'none';
}
</script>

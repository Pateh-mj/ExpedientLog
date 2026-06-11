<?php
$pageTitle = 'My Profile';
$departments = ['General' => 'General Operations', 'News' => 'News & Editorial', 'Technical' => 'Technical Support', 'Finance' => 'Finance & Accounting', 'HR' => 'Human Resources'];
?>

<div style="max-width:720px">

  <?php if (\App\Core\Session::hasFlash('errors')): ?>
    <div class="alert alert-danger" data-dismiss>
      <i class="fas fa-exclamation-circle" style="flex-shrink:0"></i>
      <ul style="margin:0;padding-left:1rem">
        <?php foreach (\App\Core\Session::getFlash('errors', []) as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php $pwd_error = \App\Core\Session::getFlash('password_error'); ?>
  <?php if ($pwd_error): ?>
    <div class="alert alert-danger" data-dismiss>
      <i class="fas fa-exclamation-circle"></i> <?= e($pwd_error) ?>
    </div>
  <?php endif; ?>

  <!-- Profile details card -->
  <div class="card" style="margin-bottom:1.5rem">
    <div class="card-header-clean">
      <h3><i class="fas fa-user-circle" style="color:var(--primary)"></i> Personal Information</h3>
    </div>
    <div style="padding:1.5rem">
      <form method="POST" action="<?= url('profile/update') ?>">
        <?= csrf_field() ?>

        <div class="form-grid-2">
          <div>
            <label class="form-label">Username</label>
            <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled
                   style="background:var(--bg);cursor:not-allowed">
            <small style="color:var(--text-muted);font-size:.75rem">Username cannot be changed</small>
          </div>
          <div>
            <label class="form-label" for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" class="form-control"
                   value="<?= e($user['full_name'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div>
            <label class="form-label" for="email">Email Address</label>
            <input type="email" id="email" name="email" class="form-control"
                   placeholder="your@email.com" value="<?= e($user['email'] ?? '') ?>">
          </div>
          <div>
            <label class="form-label" for="phone">Phone Number</label>
            <input type="text" id="phone" name="phone" class="form-control"
                   placeholder="+260 9X XXX XXXX" value="<?= e($user['phone'] ?? '') ?>">
          </div>
        </div>

        <div style="margin-bottom:1.5rem">
          <label class="form-label">Department</label>
          <div class="form-control" style="background:var(--bg);cursor:not-allowed;
               display:flex;align-items:center;gap:.5rem;color:var(--text)">
            <i class="fas fa-building" style="color:var(--text-muted);font-size:.8rem"></i>
            <?= e($user['department'] ?: 'Not assigned') ?>
          </div>
          <small style="color:var(--text-muted);font-size:.75rem">
            <i class="fas fa-lock" style="font-size:.65rem"></i>
            Department is assigned by your administrator
          </small>
        </div>

        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>
    </div>
  </div>

  <!-- Change password card -->
  <div class="card">
    <div class="card-header-clean">
      <h3><i class="fas fa-lock" style="color:var(--text-muted)"></i> Change Password</h3>
    </div>
    <div style="padding:1.5rem">
      <form method="POST" action="<?= url('profile/password') ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom:1rem">
          <label class="form-label" for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" class="form-control"
                 placeholder="Enter current password" required>
        </div>

        <div class="form-grid-2" style="margin-bottom:1.5rem">
          <div>
            <label class="form-label" for="password-input">New Password</label>
            <input type="password" id="password-input" name="new_password" class="form-control"
                   placeholder="Min. 8 characters" required minlength="8">
            <div class="strength-bar"><div class="strength-fill" id="strength-fill" style="width:0"></div></div>
            <small id="strength-label" style="font-size:.75rem;color:var(--text-muted)"></small>
          </div>
          <div>
            <label class="form-label" for="new_password_confirm">Confirm New Password</label>
            <input type="password" id="new_password_confirm" name="new_password_confirm" class="form-control"
                   placeholder="Repeat new password" required>
          </div>
        </div>

        <button type="submit" class="btn btn-outline">
          <i class="fas fa-key"></i> Update Password
        </button>
      </form>
    </div>
  </div>
</div>

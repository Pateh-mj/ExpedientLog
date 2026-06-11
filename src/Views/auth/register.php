<div class="auth-card" style="max-width:480px">
  <a href="<?= url('login') ?>" style="display:inline-flex;align-items:center;gap:.4rem;font-size:.8rem;color:var(--text-muted);margin-bottom:1.5rem">
    <i class="fas fa-arrow-left"></i> Back to login
  </a>

  <h1 style="font-size:1.4rem;font-weight:700;margin-bottom:.25rem">Create Account</h1>
  <p style="color:var(--text-muted);font-size:.875rem;margin-bottom:1.5rem">Join the <?= e(APP_NAME) ?> portal</p>

  <?php if (\App\Core\Session::hasFlash('errors')): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:.1rem"></i>
      <ul style="margin:0;padding-left:1rem">
        <?php foreach (\App\Core\Session::getFlash('errors', []) as $err): ?>
          <li><?= e($err) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= url('register') ?>">
    <?= csrf_field() ?>

    <div class="form-grid-2">
      <div>
        <label class="form-label" for="username">Username</label>
        <input type="text" id="username" name="username" class="form-control"
               placeholder="Choose username" value="<?= old('username') ?>" required minlength="3">
      </div>
      <div>
        <label class="form-label" for="full_name">Full Name</label>
        <input type="text" id="full_name" name="full_name" class="form-control"
               placeholder="Your full name" value="<?= old('full_name') ?>" required>
      </div>
    </div>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="department">Department</label>
      <select id="department" name="department" class="form-select" required>
        <?php foreach ($departments as $key => $label): ?>
          <option value="<?= e($key) ?>" <?= old('department') === $key ? 'selected' : '' ?>>
            <?= e($label) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="password-input">Password</label>
      <input type="password" id="password-input" name="password" class="form-control"
             placeholder="Min. 8 characters" required minlength="8">
      <div class="strength-bar"><div class="strength-fill" id="strength-fill" style="width:0"></div></div>
      <small style="color:var(--text-muted);font-size:.75rem" id="strength-label"></small>
    </div>

    <div style="margin-bottom:1.5rem">
      <label class="form-label" for="password_confirm">Confirm Password</label>
      <input type="password" id="password_confirm" name="password_confirm" class="form-control"
             placeholder="Repeat password" required>
    </div>

    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
      <i class="fas fa-user-plus"></i> Create Account
    </button>
  </form>
</div>

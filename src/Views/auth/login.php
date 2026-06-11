<div class="auth-card">
  <div class="auth-logo"><i class="fas fa-bolt"></i></div>
  <h1 style="text-align:center;font-size:1.5rem;font-weight:700;margin-bottom:.25rem"><?= e(APP_NAME) ?></h1>
  <p style="text-align:center;color:var(--text-muted);font-size:.875rem;margin-bottom:1.75rem">Employee Self-Service Portal</p>

  <?php if (\App\Core\Session::hasFlash('error')): ?>
    <div class="alert alert-danger">
      <i class="fas fa-exclamation-circle"></i>
      <?= e(\App\Core\Session::getFlash('error')) ?>
    </div>
  <?php endif; ?>
  <?php if (\App\Core\Session::hasFlash('success')): ?>
    <div class="alert alert-success">
      <i class="fas fa-check-circle"></i>
      <?= e(\App\Core\Session::getFlash('success')) ?>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= url('login') ?>">
    <?= csrf_field() ?>

    <div style="margin-bottom:1rem">
      <label class="form-label" for="username">Username</label>
      <div style="position:relative">
        <i class="fas fa-user" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem"></i>
        <input type="text" id="username" name="username" class="form-control"
               style="padding-left:2.4rem" placeholder="Enter your username"
               value="<?= old('username') ?>" required autofocus>
      </div>
    </div>

    <div style="margin-bottom:1.5rem">
      <label class="form-label" for="password">Password</label>
      <div style="position:relative">
        <i class="fas fa-lock" style="position:absolute;left:.85rem;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:.8rem"></i>
        <input type="password" id="password" name="password" class="form-control"
               style="padding-left:2.4rem" placeholder="Enter your password" required>
        <button type="button" id="toggle-pwd"
                style="position:absolute;right:.85rem;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:var(--text-muted)">
          <i class="fas fa-eye" id="toggle-pwd-icon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-primary btn-lg" style="width:100%;justify-content:center">
      <i class="fas fa-sign-in-alt"></i> Sign In
    </button>
  </form>

  <p style="text-align:center;margin-top:1.5rem;font-size:.85rem;color:var(--text-muted)">
    New here? <a href="<?= url('register') ?>" style="font-weight:600">Create an account</a>
  </p>

  <p style="text-align:center;font-size:.75rem;color:var(--text-muted);margin-top:1rem">
    Expedia Resources • Lusaka, Zambia
  </p>
</div>

<script>
document.getElementById('toggle-pwd').addEventListener('click', function () {
  const inp  = document.getElementById('password');
  const icon = document.getElementById('toggle-pwd-icon');
  const show = inp.type === 'password';
  inp.type   = show ? 'text' : 'password';
  icon.className = show ? 'fas fa-eye-slash' : 'fas fa-eye';
});
</script>

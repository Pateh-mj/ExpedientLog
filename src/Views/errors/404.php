<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>404 — Page Not Found</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body { font-family: Inter, sans-serif; background:#f0f3f8; display:grid; place-items:center; min-height:100vh; margin:0; }
    .box { text-align:center; }
    .code { font-size:5rem; font-weight:800; color:#004080; line-height:1; }
    p { color:#64748b; margin:.5rem 0 2rem; }
    a { display:inline-block; padding:.6rem 1.4rem; background:#004080; color:#fff; border-radius:8px; font-weight:600; text-decoration:none; }
  </style>
</head>
<body>
  <div class="box">
    <div class="code">404</div>
    <p>This page doesn't exist.</p>
    <a href="<?= url('dashboard') ?>">Go to Dashboard</a>
  </div>
</body>
</html>

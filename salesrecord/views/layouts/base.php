<!doctype html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title ?? 'SalesRecord') ?></title>
<link rel="stylesheet" href="<?= base_url('public/assets/app.css') ?>">
</head>
<body>
<header class="top">
  <div class="wrap">
    <strong>SalesRecord</strong>
    <nav>
      <?php if (is_logged_in()): ?>
        <span>Hi, <?= htmlspecialchars(user()['name']) ?> (<?= user()['role'] ?>)</span>
        | <a href="<?= base_url(user()['role']==='admin'?'admin/sales':'agent/dashboard') ?>">Dashboard</a>
        <?php if (user()['role']==='admin'): ?>
          | <a href="<?= base_url('admin/payments') ?>">Payments Ledger</a>
           | <a href="<?= base_url('admin/shareholder') ?>">Shareholder</a> 
        |  <a href="<?= base_url('admin/reports/annual') ?>">Annual Report</a> 

          | <a href="<?= base_url('admin/users') ?>">Users</a>
          | <a href="<?= base_url('admin/boards') ?>">Boards</a>
          | <a href="<?= base_url('admin/password') ?>">Change Password</a>
        <?php endif; ?>
        | <a href="<?= base_url('logout') ?>">Logout</a>
      <?php endif; ?>
    </nav>
  </div>
</header>
<main class="wrap"><?= $content ?? '' ?></main>







<style>
  /* ===== Scoped header styles (dark) — no global/body changes, no media queries ===== */
  .top{
    position: sticky; top: 0; z-index: 1000;
    background: #0b1020;                    /* dark header background */
    color: #e5e7eb;                          /* light text inside header only */
    border-bottom: 1px solid #111827;
    box-shadow: 0 2px 10px rgba(0,0,0,.35);
  }
  .top .wrap{
    display: flex; align-items: center; justify-content: space-between;
    gap: .75rem; padding: .7rem 1rem;
  }

  /* Brand */
  .top strong{
    color: #f1f5f9;
    font-weight: 700; letter-spacing: .2px;
    display: inline-flex; align-items: center; gap:.5rem;
  }
  .top strong::before{
    content:""; width:10px; height:10px; border-radius:50%;
    background: linear-gradient(135deg,#60a5fa,#34d399);
    box-shadow: 0 0 0 4px rgba(96,165,250,.15), 0 0 24px -6px #60a5fa;
  }

  /* Nav (pipes removed by hiding stray text nodes) */
  .top nav{
    display: flex; align-items: center; flex-wrap: wrap; gap: .5rem;
    font-size: 0; /* hides "|" text nodes without touching elements */
  }
  .top nav span,
  .top nav a{
    font-size: .94rem;                       /* restore size for actual elements */
    line-height: 1.2;
  }

  .top nav span{
    color: #cbd5e1;                          /* muted slate-300 */
  }

  .top nav a{
    color: #e5e7eb;                          /* light links on dark bg */
    text-decoration: none;
    padding: .38rem .55rem; border-radius: 10px;
    transition: background .18s ease, transform .08s ease, color .18s ease;
  }
  .top nav a:hover{
    background: rgba(255,255,255,.08);
    color: #ffffff;
  }
  .top nav a:focus-visible{
    outline: 2px solid rgba(96,165,250,.45);
    outline-offset: 2px;
  }

  /* Logout accent (scoped) */
  .top nav a[href$="logout"]{ color: #fca5a5; }
  .top nav a[href$="logout"]:hover{
    background: rgba(239,68,68,.15);
    color: #fecaca;
  }
</style>







</body>
</html>

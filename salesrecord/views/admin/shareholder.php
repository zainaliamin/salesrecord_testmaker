<?php
  $title = 'Admin • Shareholder';
  ob_start();

  // --- Safe local copies for display ---
  $profitAll  = isset($kpis['profit_all'])  ? (int)$kpis['profit_all']  : 0;
  $reserve15  = isset($kpis['reserve_15'])  ? (int)$kpis['reserve_15']  : 0;
  $spentTotal = isset($kpis['spent_total']) ? (int)$kpis['spent_total'] : 0;
  $available  = isset($kpis['available'])   ? (int)$kpis['available']   : 0;
  $editSpend  = $editSpend ?? null;
  $isEditing  = is_array($editSpend);
  $formAction = $isEditing ? base_url('admin/shareholder/update') : base_url('admin/shareholder/spend');
?>

<style>
  :root{
    --bg:#f7f7fb; --card:#ffffff; --ink:#1f2328; --muted:#6b7280; --line:#e9e9f2;
    --gold-1:#7c6123; --gold-2:#b8902f; --gold-3:#e9d682; --brand:#d4af37;
    --ring: 0 10px 26px rgba(0,0,0,.06), inset 0 1px 0 rgba(255,255,255,.6);
    --radius-xl:18px; --radius:12px;
  }
  @media (prefers-color-scheme: dark){
    :root{
      --bg:#0f1116; --card:#14171f; --ink:#e7e9ee; --muted:#9aa3b2; --line:#222735;
      --ring: 0 10px 30px rgba(0,0,0,.35), inset 0 1px 0 rgba(255,255,255,.06);
    }
  }

  .shell{ max-width:1100px; margin:0 auto; padding:18px 14px; color:var(--ink); }
  .hero{
    border-radius:var(--radius-xl);
    background:
      radial-gradient(1000px 380px at 50% -20%, rgba(212,175,55,.10), transparent 65%),
      linear-gradient(180deg, var(--card), var(--card));
    border:1px solid var(--line);
    box-shadow:var(--ring);
    padding:20px 20px 16px;
  }

  .dua{ text-align:center; margin:6px 0 0 0; font-weight:400; line-height:1.35; letter-spacing:.2px; }
  .dua .en{ display:block; font-size:19px; font-family:'Georgia','Times New Roman',serif; color:var(--ink); opacity:.96; }
  .dua .note{ font-size:14px; color:var(--muted); }
  .divider{
    display:block; margin:10px auto 8px; width:min(300px,60%); height:1px; border-radius:2px; position:relative;
    background:linear-gradient(90deg,transparent, rgba(212,175,55,.55), transparent);
  }
  .divider::after{
    content:""; position:absolute; left:50%; top:-3px; transform:translateX(-50%);
    width:7px; height:7px; border-radius:50%;
    background:radial-gradient(circle, #f6e7a8, #d4af37 65%, #8a6a27);
    box-shadow:0 0 12px rgba(212,175,55,.35);
  }
  .dua .ar{
    display:block; margin-top:6px;
    font-size:clamp(32px,4.2vw,38px);
    font-weight:400; letter-spacing:.15px; unicode-bidi:embed; direction:rtl;
    font-family:'Scheherazade New','Amiri','Noto Naskh Arabic','Traditional Arabic','Times New Roman',serif;
    background:linear-gradient(90deg,var(--gold-1),var(--gold-2),var(--gold-3),var(--gold-2),var(--gold-1));
    -webkit-background-clip:text; background-clip:text; color:transparent;
    text-shadow:0 1px 0 rgba(0,0,0,.05), 0 10px 26px rgba(233,214,130,.12);
  }
span.note {
        font-size: 19px !important;

}
  .kpis{ display:grid; grid-template-columns:repeat(4,1fr); gap:12px; margin-top:14px; }
  @media (max-width: 980px){ .kpis{ grid-template-columns:repeat(2,1fr); } }
  @media (max-width: 540px){ .kpis{ grid-template-columns:1fr; } }

  .kpi{
    background:var(--card); border:1px solid var(--line); border-radius:14px; padding:14px;
    box-shadow:var(--ring);
  }
  .kpi .label{ font-size:12.5px; color:var(--muted); display:flex; align-items:center; gap:8px; }
  .chip{
    padding:2px 8px; border-radius:999px; font-size:11.5px; color:#1f2328;
    background: linear-gradient(295deg, #f3d561, #d4af37, #b58010, #d4af37);
    border:1px solid rgba(0,0,0,.06);
  }
  @media (prefers-color-scheme: dark){ .chip{ color:#111; } }
  .chip.soft{ background:linear-gradient(180deg,#faf7e3,#f1e3aa); opacity:.9; }
  .kpi .val{ margin-top:6px; font-size:22px; font-weight:700; letter-spacing:.2px; }

  .card{
    background:var(--card); border:1px solid var(--line); border-radius:var(--radius);
    padding:16px; box-shadow:var(--ring);
  }
  .card h3{ margin:0 0 12px 0; font-size:18px; font-weight:600; letter-spacing:.2px; }

  .row{ display:flex; gap:12px; flex-wrap:wrap; }
  .row > label{ flex:1; min-width:220px; font-size:13px; color:var(--muted); display:flex; flex-direction:column; gap:6px; }
  input[type="number"], input[type="text"]{
    height:40px; padding:8px 12px; border-radius:10px; border:1px solid #dcdde6; background:transparent;
    color:var(--ink); font-size:14px; outline:none; transition:border-color .15s ease, box-shadow .15s ease, background .15s ease;
  }
  @media (prefers-color-scheme: dark){
    input[type="number"], input[type="text"]{ border-color:#2a3140; background:#12151c; }
  }
  input[type="number"]:focus, input[type="text"]:focus{
    border-color:var(--brand); box-shadow:0 0 0 3px rgba(212,175,55,.22);
    background:rgba(212,175,55,.02);
  }
  .btn{
    height:40px; padding:0 16px; border-radius:10px; cursor:pointer; border:1px solid #b0892b;
    font-weight:700; letter-spacing:.2px; color:#1f2328;
    background: linear-gradient(295deg, #f3d561, #d4af37, #b58010, #d4af37);
    box-shadow:0 8px 18px rgba(212,175,55,.25);
  }
  .btn:hover{ filter:brightness(1.04); }
  @media (prefers-color-scheme: dark){ .btn{ color:#111; } }
  .btn[disabled]{ opacity:.6; cursor:not-allowed; }
  .help{ margin-left:8px; font-size:12px; color:var(--muted); }

  .table-wrap{ overflow:auto; border-radius:var(--radius); }
  table{ width:100%; border-collapse:separate; border-spacing:0; }
  thead th{
    text-align:left; font-size:12.5px; font-weight:700; padding:10px 12px; letter-spacing:.2px;
    background:linear-gradient(180deg,#fafbff,#f5f7ff); color:#374151; border-bottom:1px solid var(--line);
  }
  @media (prefers-color-scheme: dark){
    thead th{ background:linear-gradient(180deg,#1a1e27,#14171f); color:#c9cfdb; } }
  }
  tbody td{ padding:10px 12px; border-bottom:1px solid var(--line); font-size:14px; }
  tbody tr:hover{ background:rgba(212,175,55,.06); }
  .td-id{ color:var(--muted); }
  .td-amt{ font-weight:700; }
  .td-note{ max-width:520px; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }

  .stack{ display:grid; gap:14px; }
</style>

<div class="shell stack">

  <!-- HERO -->
  <section class="hero">
    <h2 class="dua">
      <span class="en">In the Name of Allah <span class="note">(سُبْحَانَهُ وَتَعَالَى)</span></span>
      <span class="divider" aria-hidden="true"></span>
      <span class="ar">اللَّهُمَّ بَارِكْ لِي فِيمَا رَزَقْتَنِي</span>
    </h2>

    <!-- KPIs -->
    <div class="kpis" role="list">
      <article class="kpi" role="listitem">
        <div class="label"><span class="chip">All-time</span> Profit</div>
        <div class="val"><?= number_format($profitAll) ?></div>
      </article>
      <article class="kpi" role="listitem">
        <div class="label"><span class="chip">Reserve</span> @ 15%</div>
        <div class="val"><?= number_format($reserve15) ?></div>
      </article>
      <article class="kpi" role="listitem">
        <div class="label"><span class="chip">Outflow</span> Spent (All-time)</div>
        <div class="val"><?= number_format($spentTotal) ?></div>
      </article>
      <article class="kpi" role="listitem">
        <div class="label"><span class="chip">Available</span> To Spend</div>
        <div class="val"><?= number_format($available) ?></div>
      </article>
    </div>
  </section>

  <!-- FORM -->
  <section class="card" style="">
    <h3><?= $isEditing ? 'Edit Spend' : 'Add Spend' ?></h3>
    <form method="post" action="<?= htmlspecialchars($formAction) ?>" novalidate>
      <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
      <?php if ($isEditing): ?>
        <input type="hidden" name="id" value="<?= (int)$editSpend['id'] ?>">
      <?php endif; ?>
      <div class="row">
        <label>
          Amount (PKR)
          <input
            type="number"
            name="amount"
            min="1"
            max="<?= $available ?>"
            required
            inputmode="numeric"
            placeholder="e.g., 5,000"
            value="<?= htmlspecialchars($editSpend['amount'] ?? '') ?>">
        </label>
        <label style="flex:2;">
          Spend On (Note)
          <input type="text" name="note" maxlength="200" placeholder="e.g., Given in the name of Allah" required value="<?= htmlspecialchars($editSpend['note'] ?? '') ?>">
        </label>
      </div>
      <div style="margin-top:10px;">
        <button class="btn" type="submit"<?= (!$isEditing && $available < 1) ? ' disabled aria-disabled="true"' : '' ?>>
          <?= $isEditing ? 'Update Spend' : 'Record Spend' ?>
        </button>
        <?php if ($isEditing): ?>
          <a class="btn" style="margin-left:8px; background:#fff; border-color:#ccc; color:#374151;" href="<?= base_url('admin/shareholder') ?>">Cancel</a>
        <?php endif; ?>
        <span class="help">
          <?php if ($isEditing): ?>
            Editing spend #<?= (int)$editSpend['id'] ?>.
          <?php else: ?>
            <?= $available < 1 ? 'No available balance to spend.' : 'Instant update after submit.' ?>
          <?php endif; ?>
        </span>
      </div>
    </form>
  </section>

  <!-- TABLE -->
  <section class="card">
    <h3 style="margin:0 0 12px 0;">Spend Ledger (All-time)</h3>
    <div class="table-wrap">
      <table aria-label="Shareholder spend ledger">
        <thead>
          <tr>
            <th>ID</th>
            <th>Amount</th>
            <th>Note</th>
            <th>By</th>
            <th>Created At</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($spends)): foreach ($spends as $row): ?>
            <tr>
              <td class="td-id">#<?= (int)$row['id'] ?></td>
              <td class="td-amt"><?= number_format((int)$row['amount']) ?></td>
              <td class="td-note" title="<?= htmlspecialchars($row['note']) ?>">
                <?= htmlspecialchars($row['note']) ?>
              </td>
              <td><?= htmlspecialchars($row['by_name'] ?? '-') ?></td>
              <td><?= htmlspecialchars($row['created_at']) ?></td>
              <td style="white-space:nowrap;">
                <a class="btn" style="padding:4px 10px; font-size:12px; border-color:#9ca3af; color:#1f2937;" href="<?= base_url('admin/shareholder?edit_id='.(int)$row['id']) ?>">Edit</a>
                <form method="post" action="<?= base_url('admin/shareholder/delete') ?>" style="display:inline" onsubmit="return confirm('Delete spend #<?= (int)$row['id'] ?>?');">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token()) ?>">
                  <input type="hidden" name="id" value="<?= (int)$row['id'] ?>">
                  <button class="btn" type="submit" style="padding:4px 10px; font-size:12px; border-color:#dc2626; color:#dc2626;">Delete</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" style="text-align:center; color:var(--muted);">No spends yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>

</div>

<?php $content = ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

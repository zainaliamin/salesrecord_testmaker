<?php $title='Admin • Sale #'.$sale['id']; ob_start(); ?>
<div class="card">
  <h2>Sale #<?= (int)$sale['id'] ?></h2>
  <p><b>Submitted By (User):</b> <?= htmlspecialchars($sale['agent_user_name']) ?> (<?= htmlspecialchars($sale['agent_user_email']) ?>)</p>

  <div class="grid">
    <div><b>Full Name:</b> <?= htmlspecialchars($sale['full_name']) ?></div>
    <div><b>Phone:</b> <?= htmlspecialchars($sale['phone']) ?></div>
    <div><b>City:</b> <?= htmlspecialchars($sale['city']) ?></div>
    <div><b>School:</b> <?= htmlspecialchars($sale['school_name']) ?></div>
    <div><b>Module:</b> <?= htmlspecialchars($sale['module_name']) ?></div>
    <div><b>Package Duration (Months):</b> <?= htmlspecialchars($sale['package_duration'] ?? '-') ?></div>

    <div><b>Customer Type:</b> <?= htmlspecialchars($sale['customer_type']) ?></div>

    <div><b>Package:</b> <?= htmlspecialchars($sale['package_start_date']) ?> → <?= htmlspecialchars($sale['package_end_date']) ?></div>
    <div><b>Payment Method:</b> <?= htmlspecialchars($sale['payment_method']) ?></div>

    <div><b>Commission Amount:</b> <?= (int)$sale['commission_amount'] ?></div>
    <div><b>Sale Source:</b> <?= htmlspecialchars($sale['sale_source']) ?></div>
    <div><b>Province:</b> <?= htmlspecialchars($sale['province']) ?></div>
    <div><b>Exam Board:</b> <?= htmlspecialchars($sale['exam_board']) ?></div>

    <div><b>Agent Name (typed/auto):</b> <?= htmlspecialchars($sale['agent_name']) ?></div>

    <div><b>Amount To Pay:</b> <?= (int)$sale['amount_to_be_paid'] ?></div>
    <div><b>Amount Paid:</b> <?= (int)$sale['amount_paid'] ?></div>
    <div><b>Amount Due:</b> <?= (int)$sale['amount_due'] ?></div>
    <div><b>Next Payment Date:</b> <?= htmlspecialchars($sale['next_payment_date'] ?? '-') ?></div>
    <div><b>Status:</b> <span class="badge <?= $sale['status'] ?>"><?= $sale['status'] ?></span></div>
    <div><b>Created:</b> <?= htmlspecialchars($sale['created_at']) ?></div>
  </div>

  <?php if (!empty($sale['agent_note'])): ?>
    <div class="agent-note" style="margin-top:14px; padding:12px 14px; border:1px dashed rgba(15,23,42,.3); border-radius:12px;">
      <div style="font-weight:700; margin-bottom:4px;">Agent Note</div>
      <div style="white-space:pre-line;"><?= nl2br(htmlspecialchars($sale['agent_note'])) ?></div>
    </div>
  <?php endif; ?>

  <?php
    // show image if exists
    $proof_url = $sale['receipt_image_path'] ? base_url('public/proofs/'.$sale['receipt_image_path']) : null;
    if ($proof_url):
  ?>
    <p style="margin-top:12px"><b>Receipt Image:</b><br>
      <img src="<?= $proof_url ?>" alt="Receipt" style="max-width:420px;border:1px solid #eee;border-radius:8px">
    </p>
  <?php endif; ?>

  <?php if($sale['status']==='pending'): ?>
  <div style="margin-top:12px">
    <form method="post" action="<?= base_url('admin/sales/approve') ?>" style="display:inline-block;margin-right:8px">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
      <button>Approve</button>
    </form>
    <form method="post" action="<?= base_url('admin/sales/reject') ?>" style="display:inline-block">
      <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
      <input type="hidden" name="id" value="<?= (int)$sale['id'] ?>">
      <label>Reason</label><input name="note" required>
      <button>Reject</button>
    </form>
  </div>
  <?php else: ?>
    <p style="margin-top:12px"><i>Already <?= $sale['status'] ?>.</i></p>
  <?php endif; ?>
</div>
<style>/* =============================
   Sale Detail — No BG Changes
   Scope: .card and children only
   ============================= */
.card{
  /* no background set — uses existing page/card bg */
  color:#0f172a;
  border:1px solid rgba(2,6,23,.08);
  border-radius:16px;
  box-shadow:0 12px 34px rgba(2,6,23,.08), 0 1px 0 rgba(2,6,23,.05);
  max-width:1000px;
  margin:28px auto;
  padding:26px 26px 20px;
  box-sizing:border-box;
  font-family:ui-sans-serif, system-ui, -apple-system, "Segoe UI", Inter, Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
  -webkit-font-smoothing:antialiased;
  -moz-osx-font-smoothing:grayscale;
}

.card > h2{
  margin:0 0 6px 0;
  font-size:1.5rem;
  font-weight:800;
  letter-spacing:-.01em;
  line-height:1.25;
}

.card > p{
  margin:0 0 18px 0;
  color:#475569;
  font-size:.95rem;
}

/* Grid */
.card .grid{
  display:grid;
  grid-template-columns:repeat(auto-fit, minmax(290px,1fr));
  gap:14px 16px;
  margin-top:6px;
}

/* Data tiles — transparent, outline-only */
.card .grid > div{
  display:grid;
  grid-template-columns:auto 1fr;
  align-items:baseline;
  column-gap:10px;
  row-gap:2px;
  min-width:0;
  padding:12px 14px;
  border:1px solid rgba(2,6,23,.08);   /* subtle outline */
  border-radius:14px;
  box-shadow:0 1px 0 rgba(2,6,23,.04);
  transition:transform .14s ease, box-shadow .14s ease, border-color .14s ease;
  /* no background set */
}

.card .grid > div:hover{
  transform:translateY(-2px);
  border-color:rgba(2,6,23,.18);
  box-shadow:0 8px 22px rgba(2,6,23,.10);
}

.card .grid > div b{
  white-space:nowrap;
  color:#0b1220;
  font-weight:700;
  font-size:.88rem;
}
.card .grid > div b + *{
  color:#1f2937;
  font-weight:500;
  min-width:0;
  overflow:hidden;
  text-overflow:ellipsis;
}

/* Badges — token style, transparent chips */
.badge{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:4px 10px;
  border-radius:999px;
  font-size:.8rem;
  font-weight:700;
  letter-spacing:.18px;
  border:1px solid currentColor; /* outline uses text color */
  background:transparent;        /* no bg paint */
  text-transform:capitalize;
}
.badge.approved{ color:#059669; } /* emerald-600 */
.badge.pending { color:#d97706; } /* amber-600 */
.badge.rejected{ color:#dc2626; } /* red-600 */
.badge::before{
  content:""; width:6px;height:6px;border-radius:50%; background:currentColor;
}

/* Receipt image — no bg, just border + shadow */
.card img{
  display:block;
  max-width:100%;
  height:auto;
  margin-top:14px;
  border:1px solid rgba(2,6,23,.10);
  border-radius:14px;
  box-shadow:0 10px 30px rgba(2,6,23,.12);
  transition:transform .18s ease, box-shadow .18s ease, filter .18s ease;
}


/* Notes */
.card p[style*="margin-top"]{
  margin-top:16px !important;
  color:#374151;
  font-size:.95rem;
}
.card p[style*="margin-top"] i{ color:#334155; }

/* Reduced motion */
@media (prefers-reduced-motion: reduce){
  .card, .card *{ transition:none !important; }
}

/* Compact on small screens */
@media (max-width:560px){
  .card{ padding:22px 16px; border-radius:14px; }
  .card > h2{ font-size:1.35rem; }
  .card .grid{ gap:12px; }
  .card .grid > div{ padding:10px 12px; }
}

/* Print — preserve layout, no bg changes added */
@media print{
  .card{
    box-shadow:none;
    border:1px solid #d1d5db;
  }
  .card .grid > div{
    box-shadow:none;
    border-color:#e5e7eb;
  }
}
</style>
<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

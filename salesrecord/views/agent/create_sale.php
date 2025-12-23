<?php
$sale     = $sale ?? null;
$isEditing = is_array($sale);
$defaultAction = $isEditing ? base_url('agent/sales/update') : base_url('agent/sales/store');
$formActionAttr = htmlspecialchars($formAction ?? $defaultAction);
$selectedType = $sale['customer_type'] ?? ($initialType ?? 'new');
$selectedPayment = $sale['payment_method'] ?? 'bank_transfer';
$selectedSaleSource = $sale['sale_source'] ?? 'Manual';
$selectedProvince = $sale['province'] ?? 'Punjab';
$selectedBoard = $sale['exam_board'] ?? null;
$title = $isEditing ? 'Edit Submission' : 'New Submission';
ob_start();
?>
<div class="card">
  <h2><?= $isEditing ? 'Edit Customer Sale' : 'Add Customer Sale' ?></h2>
  <?php if ($isEditing): ?>
    <div class="card" style="margin:12px 0; padding:10px; border:1px solid #f97316; background:#fff7ed;">
      Editing Sale #<?= (int)$sale['id'] ?> (currently <?= htmlspecialchars($sale['status']) ?>). Updating will resubmit it for approval.
    </div>
  <?php endif; ?>

  <form id="saleForm" method="post" action="<?= $formActionAttr ?>" enctype="multipart/form-data" autocomplete="off">
    <input type="hidden" name="csrf" value="<?= csrf_token() ?>">
    <input type="hidden" name="old_mode" id="old_mode" value="<?= $isEditing && $selectedType === 'old' ? 'renewal' : '' ?>"><!-- 'due' | 'renewal' after Fetch -->
    <?php if ($isEditing): ?>
      <input type="hidden" name="sale_id" value="<?= (int)$sale['id'] ?>">
    <?php endif; ?>

    <!-- ========== SECTION: Type + School + Phone + Fetch ========== -->
    <div class="grid" id="sectionFetch">
      <div>
        <label>Customer Type</label>
        <select name="customer_type" id="customer_type" required>
          <option value="new" <?= ($selectedType === 'new') ? 'selected' : '' ?>>New</option>
          <option value="old" <?= ($selectedType === 'old') ? 'selected' : '' ?>>Old</option>
        </select>
      </div>

      <div>
        <label>School Name</label>
        <input name="school_name" id="school_name" value="<?= htmlspecialchars($sale['school_name'] ?? '') ?>">
      </div>

      <div>
        <label>Phone (11 digits)</label>
        <input name="phone" id="phone" maxlength="11" placeholder="03XXXXXXXXX" value="<?= htmlspecialchars($sale['phone'] ?? '') ?>">
      </div>

      <div style="display:flex;align-items:center">
        <button type="button" id="btnFetch" style="height:40px">Fetch (Old)</button>
      </div>
    </div>

    <!-- ========== SECTION: MAIN FORM (New or Old-Renewal) ========== -->
    <div id="sectionMain">
      <!-- Identity (phone is above now) -->
      <div class="grid">
        <div><label>Full Name</label><input name="full_name" id="full_name" required value="<?= htmlspecialchars($sale['full_name'] ?? '') ?>"></div>
        <div><label>City</label><input name="city" id="city" required value="<?= htmlspecialchars($sale['city'] ?? '') ?>"></div>
        <div><label>Module Name</label><input name="module_name" id="module_name" required value="<?= htmlspecialchars($sale['module_name'] ?? '') ?>"></div>
        <div><label>Package Duration (Months)</label><input name="package_duration" id="package_duration" placeholder="12, 6, 3" type='number' required value="<?= htmlspecialchars($sale['package_duration'] ?? '') ?>"></div>

      </div>

      <!-- Dates (all three on one line) -->
      <div style="display:flex; gap:12px; flex-wrap:wrap;padding:16px 0;">
        <div style="flex:1 1 200px">
          <label>Package Start Date</label>
          <input type="date" name="package_start_date" id="pkg_start" required value="<?= htmlspecialchars($sale['package_start_date'] ?? '') ?>">
        </div>
        <div style="flex:1 1 200px">
          <label>Package End Date</label>
          <input type="date" name="package_end_date" id="pkg_end" required value="<?= htmlspecialchars($sale['package_end_date'] ?? '') ?>">
        </div>
        <div style="flex:1 1 200px">
          <label>Next Payment Date (required if due)</label>
          <input type="date" name="next_payment_date" id="next_payment_date" value="<?= htmlspecialchars($sale['next_payment_date'] ?? '') ?>">
        </div>
      </div>

      <!-- Money row: Previous Price → Payable → Paid → Due → Commission -->
      <div class="grid" style="grid-template-columns: repeat(4, minmax(140px, 1fr)); gap:12px">

        <!-- Previous Package Price (read-only; shown only for Old + Renewal) -->
        <div id="lastPriceRow" style="display:none">
          <label>Previous Package Price (read-only)</label>
          <input type="number" id="last_price" readonly>
        </div>

        <!-- Payable -->
        <div>
          <label>Payable</label>
          <input type="number" min="0" name="amount_to_be_paid" id="payable" required value="<?= htmlspecialchars($sale['amount_to_be_paid'] ?? '') ?>">
          <div class="help" id="payableHelp"></div>
        </div>

        <!-- Paid -->
        <div>
          <label>Paid</label>
          <input type="number" min="0" name="amount_paid" id="paid" required value="<?= htmlspecialchars($sale['amount_paid'] ?? '') ?>">
        </div>

        <!-- Due (auto) -->
        <div>
          <label>Due (auto)</label>
          <input type="number" id="due" readonly value="<?= htmlspecialchars($sale['amount_due'] ?? '') ?>">
        </div>

        <!-- Commission (hidden for Old via JS) -->
        <div id="commissionRow">
          <label>Commission Amount (PKR)</label>
          <input type="number" min="0" id="commission" value="<?= htmlspecialchars($sale['commission_amount'] ?? 0) ?>">
          <input type="hidden" name="commission_amount" id="commission_hidden" value="<?= htmlspecialchars($sale['commission_amount'] ?? 0) ?>">
        </div>
      </div>

      <!-- Dropdowns + misc -->
      <div class="grid">
        <div>
          <label>Payment Method</label>
          <select name="payment_method">
            <option value="bank_transfer" <?= $selectedPayment==='bank_transfer'?'selected':'' ?>>Bank Transfer</option>
            <option value="easypaisa" <?= $selectedPayment==='easypaisa'?'selected':'' ?>>Easypaisa</option>
            <option value="jazzcash" <?= $selectedPayment==='jazzcash'?'selected':'' ?>>JazzCash</option>
            <option value="cash" <?= $selectedPayment==='cash'?'selected':'' ?>>Cash</option>
            <option value="other" <?= $selectedPayment==='other'?'selected':'' ?>>Other</option>
          </select>
        </div>
        <div>
          <label>Sale Source</label>
          <select name="sale_source" id="sale_source">
            <option value="Ad boost" <?= $selectedSaleSource==='Ad boost'?'selected':'' ?>>Ad Boost</option>
            <option value="Referral" <?= $selectedSaleSource==='Referral'?'selected':'' ?>>Referral</option>
            <option value="Old Customer" <?= $selectedSaleSource==='Old Customer'?'selected':'' ?>>Old Customer</option>
            <option value="Sales Officer" <?= $selectedSaleSource==='Sales Officer'?'selected':'' ?>>Sales Officer</option>
            <option value="Add classes" <?= $selectedSaleSource==='Add classes'?'selected':'' ?>>Add Classes</option>
            <option value="Manual" <?= $selectedSaleSource==='Manual'?'selected':'' ?>>Manual</option>
          </select>
        </div>
        <div>
          <label>Province</label>
          <select name="province">
            <option value="Punjab" <?= $selectedProvince==='Punjab'?'selected':'' ?>>Punjab</option>
            <option value="AJK" <?= $selectedProvince==='AJK'?'selected':'' ?>>AJK</option>
            <option value="Federal" <?= $selectedProvince==='Federal'?'selected':'' ?>>Federal</option>
          </select>
        </div>
        <div>
          <label>Exam Board</label>
          <select name="exam_board">
            <?php if (isset($boards) && is_array($boards)): ?>
              <?php foreach ($boards as $eb): ?>
                <option value="<?= htmlspecialchars($eb) ?>" <?= ($selectedBoard === $eb) ? 'selected' : '' ?>><?= htmlspecialchars($eb) ?></option>
              <?php endforeach; ?>
            <?php else: ?>
              <option value="PTB" <?= $selectedBoard==='PTB'?'selected':'' ?>>PTB</option>
              <option value="AJK" <?= $selectedBoard==='AJK'?'selected':'' ?>>AJK</option>
              <option value="Federal" <?= $selectedBoard==='Federal'?'selected':'' ?>>Federal</option>
            <?php endif; ?>
          </select>
        </div>
        <div style="grid-column:1 / -1">
          <label>Submission Note (optional)</label>
          <textarea name="agent_note" rows="2" maxlength="250" placeholder="e.g., Renewal for 2024 batch or parent request notes"><?= htmlspecialchars($sale['agent_note'] ?? '') ?></textarea>
          <div class="help">Admins will see this note alongside the submission.</div>
        </div>

        <div>
          <label>Receipt Image (jpg/png, <=3MB)</label>
          <input type="file" name="receipt_image" id="receipt_image" accept=".jpg,.jpeg,.png" <?= $isEditing ? '' : 'required' ?>>
          <?php if ($isEditing && !empty($sale['receipt_image_path'])): ?>
            <div class="help">Current file: <?= htmlspecialchars($sale['receipt_image_path']) ?> (kept unless you upload a new one).</div>
          <?php endif; ?>
        </div>
        
      </div>
    </div><!-- /sectionMain -->

    <!-- ========== SECTION: DUE-ONLY (Old + has due) ========== -->
    <div id="sectionDueOnly" style="display:none">
      <div class="grid">
        <div>
          <label>Payable Dues</label>
          <input type="number" id="due_only_payable" readonly>
          <div class="help">Current outstanding due for this school.</div>
        </div>

        <div>
          <label>Paid</label>
          <input type="number" min="1" name="amount_paid" id="due_only_paid">
          <div class="help">Enter full or partial payment (≤ payable due).</div>
        </div>

        <div>
          <label>Payment Method</label>
          <select name="paydue_payment_method" id="paydue_payment_method">
            <option value="bank_transfer">Bank Transfer</option>
            <option value="easypaisa">Easypaisa</option>
            <option value="jazzcash">JazzCash</option>
            <option value="cash" selected>Cash</option>
            <option value="other">Other</option>
          </select>
        </div>

        <div>
          <label>Receipt Image (jpg/png, <=3MB)</label>
          <input type="file" name="paydue_receipt" id="paydue_receipt" accept=".jpg,.jpeg,.png" required>
        </div>

        <div>
          <label>Next Payment Date (required if partial remains)</label>
          <input type="date" name="next_payment_date" id="due_only_next">
        </div>
        <div>
          <label>Remarks (optional)</label>
          <input type="text" name="due_note" maxlength="250" placeholder="e.g., Called principal, partial received">
        </div>
      </div>

      <div class="help" style="margin:8px 0">
        Submitted due payments now wait for admin approval before they affect the sale's balance.
      </div>
    </div><!-- /sectionDueOnly -->

    <!-- Submit -->
    <div style="margin-top:12px">
      <button id="submitBtn" type="submit"><?= $isEditing ? 'Update &amp; Resubmit' : 'Submit' ?></button>
    </div>
  </form>
</div>

<!-- ========== Script ========== -->
<script>
(function(){
  const $ = (id)=>document.getElementById(id);

  const form       = $('saleForm');
  const isEditing  = <?= $isEditing ? 'true' : 'false' ?>;
  const prevPriceFromEdit = <?= json_encode((int)($sale['amount_to_be_paid'] ?? 0)) ?>;
  const typeEl     = $('customer_type');
  const schoolEl   = $('school_name');
  const phoneEl    = $('phone');
  const saleSourceEl = document.getElementById('sale_source');
  const btnFetch   = $('btnFetch');

  const sectionMain    = $('sectionMain');
  const sectionDueOnly = $('sectionDueOnly');

  const helpEl    = $('payableHelp');
  const submitBtn = $('submitBtn');

  const lastPriceRow = $('lastPriceRow');
  const lastPrice    = $('last_price');

  // Receipt inputs (toggle required depending on mode)
  const receiptMain = $('receipt_image');
  const receiptDue  = $('paydue_receipt');

  // Main fields
  const fields = {
    full:    $('full_name'),
    phone:   $('phone'),
    city:    $('city'),
    module:  $('module_name'),
    start:   $('pkg_start'),
    end:     $('pkg_end'),
    payable: $('payable'),
    paid:    $('paid'),
    due:     $('due'),
    nextPay: $('next_payment_date')
  };

  // --- Commission helpers (UI only) ---
  const commissionRow = document.getElementById('commissionRow');
  const commissionVis = document.getElementById('commission');
  const commissionHid = document.getElementById('commission_hidden');

  function setCommissionLocked(lock){
    if (lock){
      commissionVis.value = '0';
      commissionHid.value = '0';
      commissionVis.setAttribute('disabled','disabled');
      commissionVis.setAttribute('readonly','readonly');
      commissionRow.style.opacity = 0.6;
    } else {
      commissionVis.removeAttribute('disabled');
      commissionVis.removeAttribute('readonly');
      commissionRow.style.opacity = 1;
    }
  }

  function setCommissionVisible(show){
    commissionRow.style.display = show ? '' : 'none';
    if (!show){
      commissionVis.value = '0';
      commissionHid.value = '0';
    }
  }

  function applyCommissionRules(){
    const allowOldCommission = (typeEl.value === 'old' && saleSourceEl && saleSourceEl.value === 'Add classes');
    if (typeEl.value === 'old') {
      if (allowOldCommission) {
        setCommissionVisible(true);
        setCommissionLocked(false);
      } else {
        setCommissionVisible(false);
        setCommissionLocked(true);
      }
    } else {
      setCommissionVisible(true);
      setCommissionLocked(false);
    }
  }

  commissionVis.addEventListener('input', ()=>{
    commissionHid.value = commissionVis.value || '0';
  });

  // Calculate due in main form
  function calcDue(){
    const p = parseInt(fields.payable.value || '0', 10);
    const a = parseInt(fields.paid.value    || '0', 10);
    fields.due.value = Math.max(0, p - a);
  }
  fields.paid.addEventListener('input', calcDue);
  fields.payable.addEventListener('input', calcDue);

  function setEnabled(containerEl, on){
    containerEl.querySelectorAll('input, select, textarea, button').forEach(el=>{
      if (el.id === 'btnFetch') return;
      if (on) el.removeAttribute('disabled');
      else    el.setAttribute('disabled','disabled');
    });
  }

  // ---- Modes ----

  // New: full form, commission visible & editable
  function setNewMode(){
    if (!isEditing) form.action = '<?= base_url('agent/sales/store') ?>';
    sectionMain.style.display    = '';
    sectionDueOnly.style.display = 'none';
    setEnabled(sectionMain, true);
    setEnabled(sectionDueOnly, false);

    applyCommissionRules();

    $('old_mode').value = '';
    lastPriceRow.style.display = 'none';

    // Required flags
    schoolEl.required = true;
    phoneEl.required  = true;

    // Receipt required toggles
    if (receiptMain) receiptMain.required = true;
    if (receiptDue)  receiptDue.required  = false;

    submitBtn.disabled = false;
    if (helpEl) helpEl.textContent = '';
  }

  // Old (before fetch): require fetch first; hide commission
  function setOldPreFetch(){
    if (!isEditing) form.action = '<?= base_url('agent/sales/store') ?>';
    sectionMain.style.display    = '';
    sectionDueOnly.style.display = 'none';
    setEnabled(sectionMain, true);
    setEnabled(sectionDueOnly, false);

    applyCommissionRules();

    $('old_mode').value = '';
    lastPriceRow.style.display = 'none';

    schoolEl.required = false;
    phoneEl.required  = false;

    if (receiptMain) receiptMain.required = false;
    if (receiptDue)  receiptDue.required  = false;

    submitBtn.disabled = true; // must fetch first
    if (helpEl) helpEl.textContent = 'If Old, click Fetch to load current status (due or renewal).';
  }

  // Old + due: due-only UI; commission hidden
  function setOldDueMode(dueValue){
    if (!isEditing) form.action = '<?= base_url('agent/sales/paydue') ?>';
    $('old_mode').value = 'due';

    setCommissionVisible(false);
    setCommissionLocked(true);

    sectionMain.style.display    = 'none';
    setEnabled(sectionMain, false);

    sectionDueOnly.style.display = '';
    setEnabled(sectionDueOnly, true);

    $('due_only_payable').value = String(dueValue || 0);
    $('due_only_paid').value    = '';
    $('due_only_next').value    = '';

    schoolEl.required = true;   // payDue needs school name
    phoneEl.required  = true;

    // Receipt required toggles
    if (receiptMain) receiptMain.required = false;
    if (receiptDue)  receiptDue.required  = true;

    lastPriceRow.style.display = 'none';
    submitBtn.disabled = false;
  }

  // Old + no due (renewal): main form; commission hidden; show last price
  function setOldRenewalMode(prevPrice=0){
    if (!isEditing) form.action = '<?= base_url('agent/sales/store') ?>';
    $('old_mode').value = 'renewal';

    sectionDueOnly.style.display = 'none';
    setEnabled(sectionDueOnly, false);

    sectionMain.style.display    = '';
    setEnabled(sectionMain, true);

    // Lock identity; allow package & money
    [fields.full, fields.phone, fields.city].forEach(el=>el.readOnly = true);
    [fields.module, fields.start, fields.end, fields.payable, fields.paid].forEach(el=>el.readOnly = false);

    // Show previous package price (read-only)
    lastPriceRow.style.display = '';
    lastPrice.value = String(prevPrice || 0);

    applyCommissionRules();

    schoolEl.required = true;   // renewal needs school
    phoneEl.required  = false;

    // Receipt required toggles
    if (receiptMain) receiptMain.required = true;
    if (receiptDue)  receiptDue.required  = false;

    submitBtn.disabled = false;
    if (helpEl) helpEl.textContent = 'Renewal: set new package price as Payable.';
  }

  // Toggle mode inline on type change (no full reload)
  typeEl.addEventListener('change', ()=>{
    $('old_mode').value = (isEditing && typeEl.value === 'old') ? 'renewal' : '';
    if (typeEl.value === 'old') {
      if (isEditing) {
        setOldRenewalMode(prevPriceFromEdit);
        if (btnFetch) {
          btnFetch.disabled = true;
          btnFetch.title = 'Fetch not required when editing an existing old customer';
        }
        if (helpEl) helpEl.textContent = 'Editing existing old-customer sale; fetch not required.';
      } else {
        setOldPreFetch();
        // Clear due-only inputs to force fresh fetch
        if ($('due_only_payable')) $('due_only_payable').value = '';
        if ($('due_only_paid'))    $('due_only_paid').value    = '';
        if ($('due_only_next'))    $('due_only_next').value    = '';
      }
    } else {
      setNewMode();
    }
  });

  if (saleSourceEl) {
    saleSourceEl.addEventListener('change', ()=>{
      applyCommissionRules();
    });
  }

  // Fetch for Old (STRICT: requires school + 11-digit phone)
  btnFetch.addEventListener('click', async ()=>{
    if (typeEl.value !== 'old') return;

    const name  = schoolEl.value.trim();
    const phone = phoneEl.value.trim();

    if (!name || !/^\d{11}$/.test(phone)) {
      alert('Enter BOTH school name and a valid 11-digit phone to fetch.');
      return;
    }

    try{
      const qs = new URLSearchParams();
      qs.set('school_name', name);
      qs.set('phone', phone);

      const res = await fetch('<?= base_url('agent/sales/lookup') ?>?' + qs.toString(), {headers:{'Accept':'application/json'}});
      const json = await res.json();

      if (!json.ok) {
        alert(json.msg || 'Lookup failed');
        return;
      }

      // Identity
      schoolEl.value      = json.data.school || name;
      fields.phone.value  = json.data.phone  || phoneEl.value || '';
      fields.full.value   = json.data.full_name || '';
      fields.city.value   = json.data.city || '';
      fields.module.value = json.data.module || '';
      fields.start.value  = json.data.pkg_start || '';
      fields.end.value    = json.data.pkg_end || '';

      const due     = parseInt(json.data.due || '0', 10);
      const lastPrc = parseInt(json.data.last_price || '0', 10);

      if (due > 0) {
        setOldDueMode(due);
      } else {
        setOldRenewalMode(lastPrc);
      }
    }catch(e){
      alert('Network error');
    }
  });

  // Init on load
  (function initOnLoad(){
    const isOld = (typeEl.value === 'old');
    if (isOld && isEditing) {
      setOldRenewalMode(prevPriceFromEdit);
      if (btnFetch) {
        btnFetch.disabled = true;
        btnFetch.title = 'Fetch not required when editing an existing old customer';
      }
      if (helpEl) helpEl.textContent = 'Editing existing old-customer sale; fetch not required.';
    } else if (isOld) {
      setOldPreFetch();
    } else {
      setNewMode();
    }
  })();

  // Guard: Old must fetch first
  form.addEventListener('submit', function(e){
    if (typeEl.value === 'old' && ($('old_mode').value === '')) {
      e.preventDefault();
      alert('Please click Fetch first to load dues/renewal details.');
      submitBtn.disabled = false;
      submitBtn.textContent = 'Submit';
    } else {
      submitBtn.disabled = true;
      submitBtn.textContent = 'Submitting...';
    }
  });
})();
</script>

<script>
(function () {
  const form   = document.getElementById('saleForm');
  const phone  = document.getElementById('phone');
  const due    = document.getElementById('due');
  const paid   = document.getElementById('paid');
  const payabl = document.getElementById('payable');
  const nextDt = document.getElementById('next_payment_date');

  if (!form) return;

  // --- 1) Phone must be exactly 11 digits ---
  function digitsOnly(s){ return (s || '').replace(/\D+/g, ''); }

  function validatePhoneInline() {
    if (!phone) return;
    // live-filter to digits only (optional; comment out if you don't want auto-clean)
    const cleaned = digitsOnly(phone.value);
    if (cleaned !== phone.value) phone.value = cleaned;

    if (/^\d{11}$/.test(phone.value)) {
      phone.setCustomValidity('');
    } else {
      phone.setCustomValidity('Phone must be exactly 11 digits (e.g., 03XXXXXXXXX).');
    }
  }

  if (phone) {
    phone.addEventListener('input', validatePhoneInline);
  }

  // --- 2) Toggle Next Payment Date "required" when Due > 0 ---
  function toggleNextDateRequired() {
    if (!nextDt) return;
    const dueVal = parseInt((due && due.value) ? due.value : '0', 10) || 0;
    if (dueVal > 0) nextDt.setAttribute('required', 'required');
    else            nextDt.removeAttribute('required');
  }

  // Run when money fields change (in case Due is recalculated elsewhere)
  if (due)    due.addEventListener('input', toggleNextDateRequired);
  if (paid)   paid.addEventListener('input', toggleNextDateRequired);
  if (payabl) payabl.addEventListener('input', toggleNextDateRequired);

  // Initial run
  validatePhoneInline();
  toggleNextDateRequired();

  // Final guard on submit
  form.addEventListener('submit', function (e) {
    validatePhoneInline();
    toggleNextDateRequired();

    if (phone && !/^\d{11}$/.test(phone.value)) {
      e.preventDefault();
      phone.reportValidity(); // shows native message
      return false;
    }
  });
})();
</script>



<script>
(function () {
  const section = document.getElementById('sectionDueOnly');
  const payable = document.getElementById('due_only_payable');
  const paid    = document.getElementById('due_only_paid');
  const nextDt  = document.getElementById('due_only_next');

  if (!section || !payable || !paid || !nextDt) return;

  function toInt(v){ return parseInt((v || '0'), 10) || 0; }

  function updateNextRequired() {
    const remaining = Math.max(toInt(payable.value) - toInt(paid.value), 0);
    if (remaining > 0) nextDt.setAttribute('required','required');
    else               nextDt.removeAttribute('required');
  }

  // Update while user types
  paid.addEventListener('input', updateNextRequired);
  payable.addEventListener('input', updateNextRequired);

  // Also update when the small form is shown after Fetch (programmatic value set)
  const mo = new MutationObserver(updateNextRequired);
  mo.observe(section, { attributes: true, attributeFilter: ['style', 'class'] });

  // First run
  updateNextRequired();
})();
</script>




<!-- new css -->
<style>/* ========== Sales Form — Alignment-Only Styles (No theme changes) ========== */
:root{
  --gap:12px;
  --field-h:37px;
  --radius:10px;
  --line:#dfe3e8;
}

/* Keep only this card constrained without touching other pages */
.card{
  max-width:1180px;
  margin-inline:auto;
}

/* Base grid utility used by your markup */
#saleForm .grid{
  display:grid;
  gap:var(--gap);
  padding:16px 0;
}

/* Each field block = label + control + help stacked neatly */
#saleForm .grid > div{
  display:flex;
  flex-direction:column;
  gap:6px;
  min-width:0;
}

/* Labels + help (no color changes) */
#saleForm label{ margin:0; }
#saleForm .help{ margin:0; font-size:.82rem; line-height:1.25; }

/* Uniform controls */
#saleForm input[type="text"],
#saleForm input[type="number"],
#saleForm input[type="date"],
#saleForm select{
  height:var(--field-h);
  padding:0 .65rem;
  border:1px solid var(--line);
  border-radius:var(--radius);
  width:100%;
  box-sizing:border-box;
}
#saleForm textarea{
  padding:.6rem .65rem;
  border:1px solid var(--line);
  border-radius:var(--radius);
}
#saleForm input[type="file"]{
  padding:.38rem .5rem;
  border:1px solid var(--line);
  border-radius:var(--radius);
  height:auto;
}

/* Disabled/readonly look (subtle only) */
#saleForm input[readonly],
#saleForm input[disabled],
#saleForm select[disabled]{
  background:#f5f6f8;
  color:#6b7280;
  cursor:not-allowed;
}

/* Numeric alignment */
#saleForm input[type="number"]{ text-align:right; }

/* --- Section layouts --- */

/* 1) FETCH ROW — fixed, stable columns */
#sectionFetch{
  grid-template-columns: 1fr 2fr 1.4fr auto; /* type | school | phone | button */
  align-items:end;
}
#btnFetch{
  padding:0 .95rem;
  border:1px solid var(--line);
  border-radius:var(--radius);
  cursor:pointer;
}

/* 2) IDENTITY & DROPDOWNS grids — auto-fit, clean wrap */
#sectionMain > .grid{
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
}

/* 3) DATES row (your inline flex wrapper) — equal widths/heights */
#sectionMain > div[style*="display:flex"]{
  gap:var(--gap);
}
#sectionMain > div[style*="display:flex"] > div{
  flex:1 1 260px;
  min-width:260px;
}
#sectionMain > div[style*="display:flex"] input[type="date"]{
  height:var(--field-h);
}

/* 4) DUE-ONLY section — tidy auto-fit */
#sectionDueOnly .grid{
  grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
  align-items:end;
}

/* Submit button = same height as inputs */
#submitBtn{
  height:var(--field-h);
  padding:0 1.1rem;
  border:1px solid var(--line);
  border-radius:var(--radius);
  cursor:pointer;
}
#submitBtn[disabled]{ opacity:.6; cursor:not-allowed; }

/* Smooth show/hide when your JS toggles sections */
#sectionMain, #sectionDueOnly{ transition:opacity .12s ease; }
#sectionMain[style*="display: none"],
#sectionDueOnly[style*="display: none"]{ opacity:0; }

/* ===== File input (Receipt Image) — minimal custom button color ===== */
#receipt_image{
  display:block;
  width:100%;
  max-width:520px;
  box-sizing:border-box;
  font-size:.95rem;
  /* unique vars for its button */
  --btn-bg:#111827;
  --btn-text:#ffffff;
  --btn-border:#111827;
  --btn-bg-hover:#0b1220;
  --btn-border-hover:#0b1220;
  --focus-ring:#c7d7ff;
}

/* Modern browsers */
#receipt_image::file-selector-button{
  margin-right:.6rem;
  padding:.46rem .75rem;
  border:1px solid var(--btn-border);
  border-radius:8px;
  background:var(--btn-bg);
  color:var(--btn-text);
  font-size:.9rem;
  cursor:pointer;
}

/* WebKit fallback */
#receipt_image::-webkit-file-upload-button{
  margin-right:.6rem;
  padding:.46rem .75rem;
  border:1px solid var(--btn-border);
  border-radius:8px;
  background:var(--btn-bg);
  color:var(--btn-text);
  font-size:.9rem;
  cursor:pointer;
}

/* Hover/active (minimal) */
#receipt_image::file-selector-button:hover,
#receipt_image::-webkit-file-upload-button:hover{
  background:var(--btn-bg-hover);
  border-color:var(--btn-border-hover);
}
#receipt_image::file-selector-button:active,
#receipt_image::-webkit-file-upload-button:active{
  transform:translateY(1px);
}

/* Input focus ring (keeps it subtle) */
#receipt_image:focus{
  outline:2px solid var(--focus-ring);
  outline-offset:2px;
  border-color:#cbd5e1;
}

/* Disabled state (when you toggle in due-only mode) */
#receipt_image:disabled{
  background:#f5f6f8; color:#6b7280; cursor:not-allowed;
}
#receipt_image:disabled::file-selector-button,
#receipt_image:disabled::-webkit-file-upload-button{
  background:#e5e7eb; color:#6b7280; border-color:#d1d5db; cursor:not-allowed;
  opacity:.9;
}
</style>



<?php $content=ob_get_clean(); require __DIR__.'/../layouts/base.php'; ?>

<?php
// ══════════════════════════════════════════════════════════════════════
// CLIENT ONBOARDING PORTAL
// ──────────────────────────────────────────────────────────────────────
// Public page (no login required) — gated by a token in the URL and a
// 6-digit PIN the client receives via the onboarding invite email.
// Companion to the CRM "Send Onboarding" flow in index.php.
//
// Flow:
//   1. Token comes from the URL (?token=...).
//   2. Client enters the PIN they received via email → POSTs to
//      api.php?action=crm_validate_pin.
//   3. On match, the form unlocks.
//   4. Client fills form (legal name, contact info, addresses, EIN,
//      TC721 status, etc.) and submits → api.php?action=crm_submit_onboarding.
//   5. Server auto-creates the client record + emails office@ +
//      parker@ + posts Slack message.
//   6. Confirmation page shown.
//
// Intentionally NOT using the index.php SPA shell — this surface is for
// people who aren't operators and shouldn't see the app chrome.
// ══════════════════════════════════════════════════════════════════════

$token = isset($_GET['token']) ? trim((string)$_GET['token']) : '';
$tokenIsValid = $token !== '' && preg_match('/^[a-f0-9]{32}$/i', $token);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Market Sculpt — Client Onboarding</title>
  <style>
    * { box-sizing: border-box; }
    html, body { margin: 0; padding: 0; background: #f9fafb; color: #1a1d2e; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif; }
    .wrap { max-width: 720px; margin: 0 auto; padding: 40px 20px 80px; }
    .brand-bar { display: flex; align-items: center; gap: 14px; margin-bottom: 28px; }
    .brand-mark { width: 4px; height: 36px; background: #E8751A; border-radius: 2px; }
    .brand-name { font-size: 22px; font-weight: 800; color: #1a1d2e; letter-spacing: -0.01em; }
    .brand-sub  { font-size: 12px; color: #6b7280; margin-top: 2px; }
    .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 28px; box-shadow: 0 1px 3px rgba(0,0,0,0.04); }
    .card + .card { margin-top: 16px; }
    h1 { margin: 0 0 8px; font-size: 22px; font-weight: 800; color: #1a1d2e; letter-spacing: -0.01em; }
    h2 { margin: 0 0 14px; font-size: 14px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6b7280; padding-bottom: 8px; border-bottom: 1px solid #e5e7eb; }
    p.lead { margin: 0 0 16px; font-size: 14px; color: #4b5563; line-height: 1.6; }
    .row { display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 14px; }
    .row.three { grid-template-columns: 1fr 1fr 1fr; }
    .field { margin-bottom: 14px; }
    label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #6b7280; margin-bottom: 6px; }
    label .req { color: #dc2626; margin-left: 2px; }
    input[type="text"], input[type="email"], input[type="tel"], input[type="number"], select, textarea {
      width: 100%; padding: 10px 12px; border: 1px solid #d1d5db; border-radius: 8px;
      font-size: 14px; font-family: inherit; color: #1a1d2e; background: #fff;
      transition: border-color 0.15s, box-shadow 0.15s;
    }
    textarea { resize: vertical; min-height: 70px; line-height: 1.5; }
    input:focus, select:focus, textarea:focus { outline: none; border-color: #4f46e5; box-shadow: 0 0 0 3px rgba(79,70,229,0.12); }
    input[type="file"] { display: block; padding: 8px 0; font-size: 13px; color: #4b5563; }
    .pin-input { font-family: 'SF Mono', Consolas, monospace; font-size: 28px; letter-spacing: 0.4em; text-align: center; padding: 14px 12px; }
    .btn { display: inline-block; padding: 12px 22px; border-radius: 8px; background: #4f46e5; color: #fff; border: none; font-size: 14px; font-weight: 700; cursor: pointer; font-family: inherit; transition: background 0.15s; }
    .btn:hover { background: #4338ca; }
    .btn:disabled { background: #9ca3af; cursor: not-allowed; }
    .btn-ghost { background: transparent; color: #6b7280; border: 1px solid #d1d5db; }
    .btn-ghost:hover { background: #f3f4f6; color: #1a1d2e; }
    .actions { display: flex; gap: 10px; justify-content: flex-end; margin-top: 8px; padding-top: 16px; border-top: 1px solid #e5e7eb; }
    .err { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; padding: 10px 14px; border-radius: 8px; font-size: 13px; margin-bottom: 14px; }
    .err.empty { display: none; }
    .hint { font-size: 11px; color: #6b7280; margin-top: 4px; line-height: 1.4; }
    .pill { display: inline-block; padding: 3px 10px; border-radius: 99px; font-size: 11px; font-weight: 700; }
    .pill.success { background: #dcfce7; color: #15803d; }
    .tc721-block { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; padding: 16px; }
    .tc721-block label.radio { display: flex; align-items: flex-start; gap: 10px; cursor: pointer; padding: 10px 0; border-bottom: 1px solid #f3f4f6; font-weight: 600; font-size: 13px; color: #1a1d2e; text-transform: none; letter-spacing: 0; margin-bottom: 0; }
    .tc721-block label.radio:last-of-type { border-bottom: none; }
    .tc721-block input[type="radio"] { margin-top: 3px; accent-color: #4f46e5; }
    .tc721-sub { margin-top: 12px; padding: 12px; background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; display: none; }
    .tc721-sub.active { display: block; }
    .success-banner { background: linear-gradient(135deg, #dcfce7, #f0fdf4); border: 1px solid #86efac; border-radius: 12px; padding: 32px; text-align: center; }
    .success-banner .check { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; background: #16a34a; color: #fff; border-radius: 50%; font-size: 28px; margin-bottom: 14px; }
    .success-banner h1 { color: #15803d; }
    .success-banner p { color: #166534; max-width: 460px; margin: 0 auto; font-size: 14px; line-height: 1.6; }
  </style>
</head>
<body>
<div class="wrap">

  <div class="brand-bar">
    <div class="brand-mark"></div>
    <div>
      <div class="brand-name">Market Sculpt</div>
      <div class="brand-sub">Client Onboarding Portal</div>
    </div>
  </div>

<?php if (!$tokenIsValid): ?>
  <div class="card">
    <h1>Invalid invitation link</h1>
    <p class="lead">This onboarding link is missing or malformed. Please check the link in the email we sent, or reach out to MarketSculpt for a fresh invitation.</p>
  </div>
<?php else: ?>

  <!-- Step 1: PIN gate -->
  <div class="card" id="step-pin">
    <h1>Enter your security PIN</h1>
    <p class="lead">Check the email we sent for your 6-digit PIN. You'll only need to enter this once for the form to unlock.</p>
    <div class="err empty" id="pin-err"></div>
    <div class="field">
      <label>Security PIN <span class="req">*</span></label>
      <input type="text" id="pin-input" class="pin-input" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" autocomplete="off" />
      <div class="hint">6 digits, sent to your email when MarketSculpt initiated this invitation.</div>
    </div>
    <div class="actions">
      <button type="button" class="btn" id="pin-submit-btn" onclick="validatePin()">Unlock Form →</button>
    </div>
  </div>

  <!-- Step 2: Form (hidden until PIN validates) -->
  <form id="step-form" onsubmit="submitOnboarding(event)" style="display:none;">

    <div class="card">
      <h1>Welcome to MarketSculpt</h1>
      <p class="lead">A quick set of questions so we can get your account set up and ready for the first quote. Should take about 5 minutes.</p>
      <p class="lead" id="form-preview-line" style="margin-bottom:0; font-size:13px; color:#6b7280;"></p>
    </div>

    <div class="card">
      <h2>Business Information</h2>
      <div class="field">
        <label>Legal Business Name <span class="req">*</span></label>
        <input type="text" name="legal_name" id="form-legal-name" required />
        <div class="hint">As it appears on your tax filings — for invoicing + tax exemption purposes.</div>
      </div>
      <div class="row">
        <div class="field">
          <label>DBA (if different)</label>
          <input type="text" name="dba" />
        </div>
        <div class="field">
          <label>Business Type</label>
          <div style="position:relative;">
            <select name="business_type">
              <option value="">— Select —</option>
              <option value="LLC">LLC</option>
              <option value="Corporation">Corporation (Inc.)</option>
              <option value="S-Corp">S-Corporation</option>
              <option value="Partnership">Partnership</option>
              <option value="Sole Proprietor">Sole Proprietor</option>
              <option value="Other">Other</option>
            </select>
          </div>
        </div>
      </div>
      <div class="row">
        <div class="field">
          <label>EIN / Tax ID</label>
          <input type="text" name="ein" placeholder="XX-XXXXXXX" />
        </div>
        <div class="field">
          <label>Year Established</label>
          <input type="number" name="year_established" min="1900" max="2100" />
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Primary Contact</h2>
      <div class="row">
        <div class="field">
          <label>Contact Name <span class="req">*</span></label>
          <input type="text" name="contact_name" id="form-contact-name" required />
        </div>
        <div class="field">
          <label>Title</label>
          <input type="text" name="contact_title" />
        </div>
      </div>
      <div class="row">
        <div class="field">
          <label>Email <span class="req">*</span></label>
          <input type="email" name="email" id="form-email" required />
        </div>
        <div class="field">
          <label>Phone</label>
          <input type="tel" name="phone" />
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Addresses</h2>
      <div class="field">
        <label>Billing Address <span class="req">*</span></label>
        <textarea name="billing_address" rows="3" required placeholder="Street, City, State, ZIP"></textarea>
      </div>
      <div class="field">
        <label style="display:flex; align-items:center; gap:8px; text-transform:none; letter-spacing:0; font-size:13px; font-weight:600; color:#1a1d2e;">
          <input type="checkbox" id="ship-same" onchange="onShipSameToggle()" style="accent-color:#4f46e5;" />
          Shipping address is the same as billing
        </label>
      </div>
      <div class="field" id="shipping-wrap">
        <label>Shipping Address</label>
        <textarea name="shipping_address" rows="3" placeholder="Street, City, State, ZIP"></textarea>
      </div>
    </div>

    <div class="card">
      <h2>Payment Preferences</h2>
      <div class="row">
        <div class="field">
          <label>Preferred Payment Method</label>
          <div style="position:relative;">
            <select name="payment_method">
              <option value="">— Select —</option>
              <option value="ACH">ACH (bank transfer)</option>
              <option value="Wire">Wire transfer</option>
              <option value="Credit Card">Credit card</option>
              <option value="Check">Check</option>
            </select>
          </div>
        </div>
        <div class="field">
          <label>Net Terms Requested</label>
          <div style="position:relative;">
            <select name="net_terms">
              <option value="">— None —</option>
              <option value="Due on receipt">Due on receipt</option>
              <option value="Net 15">Net 15</option>
              <option value="Net 30">Net 30</option>
              <option value="Net 60">Net 60</option>
            </select>
          </div>
          <div class="hint">Subject to approval — provide trade references in Notes below if you'd like to expedite.</div>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Sales Tax Exemption (TC721)</h2>
      <p class="lead">If you're a Utah-based reseller, complete a TC721 so we can sell to you tax-free.</p>
      <div class="tc721-block">
        <label class="radio">
          <input type="radio" name="tc721_status" value="upload" onchange="onTc721Change(this.value)" />
          <span>I have a completed TC721 — I'll upload it</span>
        </label>
        <label class="radio">
          <input type="radio" name="tc721_status" value="inline" onchange="onTc721Change(this.value)" />
          <span>I don't have one — let me fill it out here</span>
        </label>
        <label class="radio">
          <input type="radio" name="tc721_status" value="not_applicable" checked onchange="onTc721Change(this.value)" />
          <span>Not applicable / not a Utah reseller</span>
        </label>

        <div class="tc721-sub" id="tc721-upload-block">
          <label>Upload TC721 (PDF, PNG, or JPG)</label>
          <input type="file" name="tc721_file" id="tc721-file" accept=".pdf,.png,.jpg,.jpeg" />
          <div class="hint">We'll keep this on file with your account. Max 10 MB.</div>
        </div>

        <div class="tc721-sub" id="tc721-inline-block">
          <div class="row">
            <div class="field">
              <label>Sales Tax License No.</label>
              <input type="text" name="tc721_license" />
            </div>
            <div class="field">
              <label>Issuing State</label>
              <input type="text" name="tc721_state" placeholder="UT" maxlength="2" />
            </div>
          </div>
          <div class="field">
            <label>Reseller Type</label>
            <div style="position:relative;">
              <select name="tc721_type">
                <option value="">— Select —</option>
                <option value="Retailer">Retailer</option>
                <option value="Wholesaler">Wholesaler</option>
                <option value="Manufacturer">Manufacturer</option>
                <option value="Other">Other</option>
              </select>
            </div>
          </div>
          <div class="field">
            <label>Description of goods purchased for resale</label>
            <textarea name="tc721_description" rows="2" placeholder="e.g. Promotional products, custom merchandise"></textarea>
          </div>
          <div class="hint">By submitting this form you certify the above information is correct and that the purchases are for resale.</div>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Anything else?</h2>
      <div class="field">
        <label>Notes (optional)</label>
        <textarea name="notes" rows="4" placeholder="Trade references, special compliance needs, product categories you're focused on — anything that helps us serve you better."></textarea>
      </div>
    </div>

    <div class="card">
      <div class="err empty" id="submit-err"></div>
      <div class="actions">
        <button type="submit" class="btn" id="submit-btn">Submit Onboarding</button>
      </div>
    </div>
  </form>

  <!-- Step 3: Success (hidden until form submits) -->
  <div class="success-banner" id="step-done" style="display:none;">
    <div class="check">✓</div>
    <h1>You're all set!</h1>
    <p>Your information has been received. The MarketSculpt team has been notified and will reach out shortly to get your first quote moving. You can close this tab.</p>
  </div>

<?php endif; ?>

</div>

<script>
  const TOKEN = <?php echo json_encode($token); ?>;
  let validatedPin = '';

  function _err(elId, msg) {
    const el = document.getElementById(elId);
    if (!el) return;
    if (msg) { el.textContent = msg; el.classList.remove('empty'); }
    else     { el.textContent = '';  el.classList.add('empty'); }
  }

  function onShipSameToggle() {
    const same = document.getElementById('ship-same').checked;
    const wrap = document.getElementById('shipping-wrap');
    const ta   = wrap.querySelector('textarea');
    wrap.style.display = same ? 'none' : '';
    if (same && ta) ta.value = '';
  }

  function onTc721Change(value) {
    document.getElementById('tc721-upload-block').classList.toggle('active', value === 'upload');
    document.getElementById('tc721-inline-block').classList.toggle('active', value === 'inline');
  }

  async function validatePin() {
    const pin = (document.getElementById('pin-input').value || '').trim();
    _err('pin-err', '');
    if (!/^\d{6}$/.test(pin)) {
      _err('pin-err', 'PIN must be 6 digits.');
      return;
    }
    const btn = document.getElementById('pin-submit-btn');
    btn.disabled = true; btn.textContent = 'Checking…';
    try {
      const r = await fetch('api.php?action=crm_validate_pin', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ token: TOKEN, pin })
      }).then(r => r.json());
      if (!r.ok) { _err('pin-err', r.error || 'Could not validate PIN.'); return; }
      validatedPin = pin;
      document.getElementById('step-pin').style.display = 'none';
      document.getElementById('step-form').style.display = '';
      // Pre-fill known fields from the invite (the operator already
      // captured these on the CRM card).
      const preview = [];
      if (r.company) { preview.push(`Onboarding for: ${r.company}`); document.getElementById('form-legal-name').value = r.company; }
      if (r.contact) { document.getElementById('form-contact-name').value = r.contact; }
      if (r.email)   { document.getElementById('form-email').value        = r.email;   }
      document.getElementById('form-preview-line').textContent = preview.join(' · ');
    } catch (e) {
      _err('pin-err', 'Network error — please try again.');
    } finally {
      btn.disabled = false; btn.textContent = 'Unlock Form →';
    }
  }

  // Allow Enter to submit PIN
  document.addEventListener('DOMContentLoaded', () => {
    const pinIn = document.getElementById('pin-input');
    if (pinIn) {
      pinIn.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); validatePin(); } });
      pinIn.focus();
    }
  });

  async function submitOnboarding(e) {
    e.preventDefault();
    _err('submit-err', '');
    const form = document.getElementById('step-form');
    const fd   = new FormData(form);
    const obj  = {};
    fd.forEach((v, k) => { if (k !== 'tc721_file') obj[k] = v; });
    // If "same as billing" was checked, mirror billing into shipping
    // so the operator sees both fields filled on the client record.
    if (document.getElementById('ship-same').checked) {
      obj.shipping_address = obj.billing_address;
    }
    const btn = document.getElementById('submit-btn');
    btn.disabled = true; btn.textContent = 'Submitting…';
    try {
      const r = await fetch('api.php?action=crm_submit_onboarding', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
        body: JSON.stringify({ token: TOKEN, pin: validatedPin, form: obj })
      }).then(r => r.json());
      if (!r.ok) {
        _err('submit-err', r.error || 'Submission failed.');
        btn.disabled = false; btn.textContent = 'Submit Onboarding';
        return;
      }
      form.style.display = 'none';
      document.getElementById('step-done').style.display = '';
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } catch (e) {
      _err('submit-err', 'Network error — please try again.');
      btn.disabled = false; btn.textContent = 'Submit Onboarding';
    }
  }
</script>
</body>
</html>

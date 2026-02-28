// resources/js/app.js (FULL DROP-IN)
//
// ✅ Includes your existing code
// ✅ Adds Option A (global Alpine module) for insuranceInspectionModule
// ✅ Keeps modal AJAX step navigation + Alpine.initTree()
// ✅ Keeps your existing phone + payer handlers
//
// IMPORTANT: You MUST remove the <script> block from _insurance_inspection.blade.php
// (Option A means module is loaded once globally)
//
// Build: npm run build   (or npm run dev)

import './bootstrap';

// ✅ load PWA helper early (register SW + set window.GS_PWA + events)
import './pwa';

import Alpine from 'alpinejs';
window.Alpine = Alpine;

// ✅ Option A: load the insurance inspection module globally (once)
// This file will define: window.insuranceInspectionModule + window.__vaultSelect
import './modules/insurance/inspection';
window.insuranceInspectionModule = window.__insuranceInspectionModuleExport;

import './modules/insurance/quotation';
window.insuranceQuotationCard = window.__insuranceQuotationCardExport;

/**
 * ✅ Register Alpine components/globals BEFORE Alpine.start()
 * This is critical for dynamically injected modal HTML that uses x-data="jobParts(...)"
 */
document.addEventListener('alpine:init', () => {
    // Parts table component used by jobs.partials.shared.parts
    Alpine.data('jobParts', (opts = {}) => {
        const rows = Array.isArray(opts.rows) ? opts.rows : [];
        const inventory = Array.isArray(opts.inventory) ? opts.inventory : [];

        return {
            rows,
            inventory,

            addRow() {
                this.rows.push({
                    description: '',
                    quantity: 1,
                    unit_price: 0,
                    line_total: 0,
                });
            },

            removeRow(i) {
                this.rows.splice(i, 1);
            },

            // Optional helper if your UI needs computed totals
            rowTotal(row) {
                const qty = parseFloat(row?.quantity ?? 0) || 0;
                const unit = parseFloat(row?.unit_price ?? 0) || 0;
                return qty * unit;
            },

            // Optional: keep line_total synced if your blade uses it
            syncLineTotal(i) {
                const row = this.rows[i];
                if (!row) return;
                row.line_total = this.rowTotal(row);
            },
        };
    });
});

// ============================
// Insurance: cross-card rehydrate
// ============================
async function rehydrateInsuranceCard(url, containerId) {
  const el = document.getElementById(containerId);
  if (!el) return;

  try {
    const res = await fetch(url, {
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'text/html',
      },
      credentials: 'same-origin',
      cache: 'no-store',
    });

    if (!res.ok) {
      console.warn(`[rehydrateInsuranceCard] non-OK ${res.status} for`, url);
      return;
    }

    const html = await res.text();

    // 🔒 Hard reset container before swap (prevents stale listeners)
    el.innerHTML = '';
    el.insertAdjacentHTML('afterbegin', html);

    // 🔒 Re-init Alpine safely
    if (window.Alpine && typeof window.Alpine.initTree === 'function') {
      window.Alpine.initTree(el);
    }

  } catch (e) {
    console.warn('[rehydrateInsuranceCard] failed', e);
  }
}

window.rehydrateInsuranceCard = rehydrateInsuranceCard;

// When quotation is submitted, refresh Approval card from server truth
window.addEventListener('quotation:submitted', (ev) => {
  const jobId = Number(ev?.detail?.jobId || 0);
  if (!jobId) return;

  // Requires: <div id="insurance-approval-card">...</div> in Blade
  // Requires: GET /jobs/{job}/insurance/approval-card endpoint
  rehydrateInsuranceCard(`/jobs/${jobId}/insurance/approval-card`, 'insurance-approval-card');
});

// When inspection is completed, refresh Quotation card from server truth
window.addEventListener('inspection:completed', (ev) => {
  const jobId = Number(ev?.detail?.jobId || 0);
  if (!jobId) return;

  // Requires: <div id="insurance-quotation-card">...</div> in Blade
  // Requires: GET /jobs/{job}/insurance/quotation-card endpoint
  rehydrateInsuranceCard(`/jobs/${jobId}/insurance/quotation-card`, 'insurance-quotation-card');
});

// ============================
// Insurance: Approval -> Repair instant unlock (AJAX approve)
// ============================
document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (!form || !form.matches('form[data-insurance-approve]')) return;

  e.preventDefault();

  const jobId = Number(form.dataset.jobId || 0);
  if (!jobId) {
    alert('Missing job id on approve form.');
    return;
  }

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const body = new FormData(form);

  try {
    const res = await fetch(form.action, {
      method: 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body,
    });

    // If backend returns 302 redirect, fetch follows it and still ends OK-ish.
    // Treat any 2xx as success, otherwise show error.
    const data = await res.json().catch(() => ({}));

    if (!res.ok || data.ok === false) {
    alert(data.message || `Approval failed (${res.status})`);
    return;
    }

    // ✅ Rehydrate approval (badge/status) and repair (unlock state) from DB truth
    await rehydrateInsuranceCard(`/jobs/${jobId}/insurance/approval-card`, 'insurance-approval-card');
    await rehydrateInsuranceCard(`/jobs/${jobId}/insurance/repair-card`, 'insurance-repair-card');

  } catch (err) {
    console.error(err);
    alert('Approval failed (network error).');
  }
}, true);

Alpine.start();

import intlTelInput from "intl-tel-input";
import "intl-tel-input/build/css/intlTelInput.css";

/**
 * ============================
 * Modal Wizard Navigation
 * ============================
 * Usage patterns in Blade:
 *  - <button data-job-step="{{ route('jobs.create.step2', $ctxModal) }}">Back</button>
 *  - <a data-job-step href="{{ route('jobs.create.step3', $ctxModal) }}">Continue</a>
 *
 * OR direct onclick:
 *  - onclick="loadCreateJobStep('...')"
 */
const GS_MODAL_IDS = {
    modal: 'createJobModal',
    body: 'createJobModalBody',
    backdrop: 'createJobModalBackdrop',
};

// Expose a global function (used by onclick handlers)
window.loadCreateJobStep = async function (url) {
    try {
        const body = document.getElementById(GS_MODAL_IDS.body);

        // If modal body missing, fallback to normal navigation
        if (!body) {
            window.location.href = url;
            return;
        }

        // Simple loading UI
        body.innerHTML = `
            <div class="p-6 text-sm text-slate-500">
                Loading...
            </div>
        `;

        const res = await fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html',
            },
            credentials: 'same-origin',
        });

        if (!res.ok) {
            window.location.href = url;
            return;
        }

        const html = await res.text();
        body.innerHTML = html;

        // ✅ Re-init Alpine on injected DOM
        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
            window.Alpine.initTree(body);
        }
    } catch (err) {
        console.error('loadCreateJobStep() failed:', err);
        window.location.href = url;
    }
};

// Safe close helper (used by your existing Close buttons)
window.closeCreateJobModal = function () {
    const modal = document.getElementById(GS_MODAL_IDS.modal);
    const body = document.getElementById(GS_MODAL_IDS.body);
    const backdrop = document.getElementById(GS_MODAL_IDS.backdrop);

    if (body) body.innerHTML = '';

    // If you use "hidden" class for modals/backdrop
    if (modal) modal.classList.add('hidden');
    if (backdrop) backdrop.classList.add('hidden');

    // If you use aria attributes, keep it graceful
    if (modal) modal.setAttribute('aria-hidden', 'true');
};

// ✅ Delegated Back/Continue handler
// Any click on <a data-job-step> or <button data-job-step> will load that URL into the modal
document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-job-step]');
    if (!el) return;

    // Only intercept if modal body exists (means modal mode is available)
    const body = document.getElementById(GS_MODAL_IDS.body);
    if (!body) return;

    const url =
        el.getAttribute('data-job-step') ||
        (el.tagName === 'A' ? el.getAttribute('href') : null);

    if (!url) return;

    e.preventDefault();
    window.loadCreateJobStep(url);
});

// ============================
// Phone input init (as you had)
// ============================
document.addEventListener("DOMContentLoaded", () => {
    const input = document.querySelector("#phone_input");
    if (!input) return;

    const hiddenE164 = document.querySelector("#phone_e164");
    if (!hiddenE164) return;

    const iti = intlTelInput(input, {
        initialCountry: "ke",
        nationalMode: false,
        separateDialCode: false,
        autoPlaceholder: "aggressive",
        formatOnDisplay: true,
        utilsScript: "/build/intl-tel-input-utils.js",
    });

    const sync = () => {
        const number = iti.getNumber(); // +254...
        hiddenE164.value = number || "";
    };

    input.addEventListener("change", sync);
    input.addEventListener("keyup", sync);
    input.addEventListener("countrychange", sync);

    sync();
});

// ============================
// Payer type change handler (as you had)
// ============================
document.addEventListener('change', function (e) {
    if (!e.target.matches('#payer_type')) return;

    const root = e.target.closest('[data-job-form-root]') || document;

    const payer = e.target.value || '';

    const body = root.querySelector('#jobFormBody');
    const payerFields = root.querySelector('#payerFields');

    const individual = root.querySelector('#payer_individual');
    const company    = root.querySelector('#payer_company');
    const insurance  = root.querySelector('#payer_insurance');

    const unlocked = payer !== '';

    // Show / hide main body
    if (body) body.style.display = unlocked ? '' : 'none';

    // Show / hide payer container
    if (payerFields) payerFields.style.display = unlocked ? '' : 'none';

    // Hide all sub sections
    [individual, company, insurance].forEach(el => {
        if (el) el.style.display = 'none';
    });

    if (!unlocked) return;

    // Show selected section
    if (payer === 'company' && company) {
        company.style.display = '';
    }
    else if (payer === 'insurance' && insurance) {
        insurance.style.display = '';
    }
    else if (individual) {
        individual.style.display = '';
    }
});

document.addEventListener('submit', async (e) => {
  const form = e.target;
  if (!form) return;

  // only intercept the Step 3 wizard form inside the modal
  if (!form.closest('#createJobModal')) return;
  if (!['createJobForm','createJobStep2Form'].includes(form.id)) return;   // ✅ critical

  e.preventDefault();

  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const action = form.getAttribute('action') || window.location.href;

  const formData = new FormData(form);

  try {
    const res = await fetch(action, {
      method: form.getAttribute('method') || 'POST',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': csrf,
        'Accept': 'application/json',
      },
      credentials: 'same-origin',
      body: formData,
    });

      // ✅ CRITICAL: if backend returned a redirect, fetch follows it BUT browser won't navigate.
      // Force navigation so wizard Step 2 → Step 3 works.
      if (res.redirected && res.url) {
        const u = new URL(res.url, window.location.origin);
        u.searchParams.delete('modal');

        if (typeof window.closeCreateJobModal === 'function') {
          window.closeCreateJobModal();
        }

        window.location.href = u.toString();
        return;
      }

      const data = await res.json().catch(() => ({}));

    // ✅ SUCCESS (wizard): redirect to page ONLY for insurance + company
    // Works even if backend forgets to send next_url
    if (res.ok) {

        // ✅ Universal wizard redirect (Step 2 + Step 3)
        if (data && typeof data.next_url === 'string' && data.next_url.length) {
            const u = new URL(data.next_url, window.location.origin);
            u.searchParams.delete('modal');

            if (typeof window.closeCreateJobModal === 'function') {
                window.closeCreateJobModal();
            }

            window.location.href = u.toString();
            return;
        }


        const payerType = String(
            (data && data.payer_type) ?? formData.get('payer_type') ?? ''
        ).toLowerCase();

        const isPageFlow = payerType === 'insurance' || payerType === 'company';

        // next_url from backend OR fallback build from known Step-2 path + job_id
        const nextUrl =
            (data && typeof data.next_url === 'string' && data.next_url.length)
                ? data.next_url
                : (data && data.job_id ? `/jobs/create/step-2?job=${data.job_id}` : '');

        if (isPageFlow && nextUrl) {
            const u = new URL(nextUrl, window.location.origin);
            u.searchParams.delete('modal');

            if (typeof window.closeCreateJobModal === 'function') {
                window.closeCreateJobModal();
            }

            window.location.href = u.toString();
            return;
        }
    }



    // ✅ SUCCESS (normal): explicit ok=true
    if (res.ok && data && data.ok === true) {
    // close modal
    const modalEl = document.getElementById('createJobModal');
    if (modalEl) {
        const inst = window.bootstrap?.Modal?.getInstance(modalEl);
        if (inst) inst.hide();
        else modalEl.classList.add('hidden');
    }
    return;
    }

    // ❌ FAIL
    console.error('Save failed:', data);
    alert(data.message || `Save failed (${res.status})`);
    return;


    // optional: refresh jobs list, etc.
    // location.reload();

  } catch (err) {
    console.error(err);
    alert('Save failed (network error).');
  }
}, true);

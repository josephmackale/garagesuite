// resources/js/modules/insurance/quotation.js
// Quotation card Alpine module (UI state + add/edit/delete lines + live totals)
// ✅ Patched to support your current Blade (Quick Add + inline edit + patch/save events)
// ✅ Keeps your existing dispatch-event wiring + saveDraft/submitQuote endpoints
// ✅ Normalizes line.type to canonical keys: labour | parts | materials | sublet
// ✅ Fixes "false" string bug for editable/lockedByInspection (safe boolean parsing)
//
// 🔧 DROP-IN PATCH (touching ONLY relevant code):
// - Fix submitQuote(): stop assigning to getter properties (subtotal/total) which crashes and shows "Network error".
// - Make submitQuote() fetch headers/credentials match saveDraft() to avoid HTML redirects.
// - Ensure we surface real server errors instead of generic "Network error".

function money(n) {
  const x = Number(n || 0);
  return Number.isFinite(x) ? x : 0;
}

function round2(n) {
  return Math.round(money(n) * 100) / 100;
}

function computeLineAmount(line) {
  const qty = money(line.qty);
  const unit = money(line.unit_price);
  return round2(qty * unit);
}

// ✅ canonical type keys to match your Blade <select> values
function normalizeType(t) {
  const s = String(t || '').trim().toLowerCase();
  if (!s) return 'labour';
  if (['labour', 'labor'].includes(s)) return 'labour';
  if (['part', 'parts'].includes(s)) return 'parts';
  if (['paint', 'materials', 'material'].includes(s)) return 'materials';
  if (['sublet', 'outsourced'].includes(s)) return 'sublet';
  return s;
}

function toastState() {
  return { show: false, type: 'ok', message: '' };
}

function showToast(ctx, type, message) {
  ctx.toast.type = type;
  ctx.toast.message = message;
  ctx.toast.show = true;
  clearTimeout(ctx.__toastTimer);
  ctx.__toastTimer = setTimeout(() => (ctx.toast.show = false), 3500);
}

// ✅ FIX: safe boolean parsing (handles "false"/"true" strings)
function toBool(v) {
  if (v === true || v === 1) return true;
  if (v === false || v === 0 || v === null || v === undefined) return false;

  const s = String(v).trim().toLowerCase();
  if (['1', 'true', 'yes', 'y', 'on'].includes(s)) return true;
  if (['0', 'false', 'no', 'n', 'off', ''].includes(s)) return false;

  return Boolean(v);
}

function isFinalLockedStatus(status) {
  const s = String(status || '').toLowerCase();
  return s === 'submitted' || s === 'approved';
}

window.__insuranceQuotationCardExport = function insuranceQuotationCard(config = {}) {
  return {
    // --- config / gates
    status: String(config.status || 'draft'),

    // 🔐 Server-enforced lock (Phase 2 rule — JS must never unlock)
    editable: (function () {
      const s = String(config.status || 'draft').toLowerCase();
      if (['submitted', 'approved'].includes(s)) return false;
      return toBool(config.editable);
    })(),

    lockedByInspection: (function () {
      // For submitted/approved we are locked by STATUS, not inspection
      return toBool(config.lockedByInspection);
    })(),


    version: Number(config.version || 1),
    jobId: Number(config.jobId || config.job_id || 0),

    // endpoints (optional for now)
    saveUrl: config.saveUrl || null,
    submitUrl: config.submitUrl || null,
    csrf:
      config.csrf ||
      (document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''),

    // --- data
    lines: Array.isArray(config.initialLines)
      ? config.initialLines.map((l) => {
          const qty = money(l.qty ?? 1);
          const unit_price = money(l.unit_price ?? 0);
          const amount = money(l.amount ?? computeLineAmount({ qty, unit_price }));
          return {
            type: normalizeType(l.type),
            description: String(l.description || '').trim(),
            qty,
            unit_price,
            amount,
          };
        })
      : [],

    tax: money(config.tax || 0),
    discount: money(config.discount || 0),
    vatRate: Number(config.vatRate ?? 0.16),
    vatEnabled: true,

    // --- UI state
    toast: toastState(),
    saving: false,
    submitting: false,
    lastSavedAt: null,

    // ✅ NEW: inline edit + quick add state (matches your Blade)
    editingIndex: null,
    quick: { type: 'labour', description: '', qty: 1, amount: 0 },

    // --- existing modal (kept for backwards compatibility / future modal UI)
    modal: {
      open: false,
      mode: 'add', // add | edit
      index: null,
      type: 'labour',
      description: '',
      qty: 1,
      unit_price: 0,
    },

    init() {
      this.lastSavedAt = new Date().toISOString();

      // Listen to your existing dispatch events from the Blade card
      window.addEventListener('quotation:add-line', () => this.openAdd());
      window.addEventListener('quotation:edit-line', (e) => this.openEdit(e?.detail?.index));
      window.addEventListener('quotation:delete-line', (e) => this.deleteLine(e?.detail?.index));
      window.addEventListener('quotation:save-draft', () => this.saveDraft());
      window.addEventListener('quotation:submit', () => this.submitQuote());
      window.addEventListener('quotation:import-suggestions', () => {
        showToast(this, 'ok', 'Import suggestions wiring next.');
      });

      // ✅ NEW: inline patch/save events used by your table inputs/buttons
      window.addEventListener('quotation:patch-line', (e) => {
        const d = e?.detail || {};
        this.patchLine(d.index, d.field, d.value);
      });

      window.addEventListener('quotation:save-line', () => {
        // Inline "Done" button - no backend needed yet (saveDraft button handles persistence)
        showToast(this, 'ok', 'Updated.');
      });

      // (Kept from your earlier pattern)
      window.addEventListener('inspection:show-checklist', () => {
        this.tab = 'checklist'; // harmless if unused
      });

    },

    // --- derived
    get linesCount() {
      return this.lines.length;
    },

    get subtotal() {
      return round2(this.lines.reduce((sum, l) => sum + money(l.amount), 0));
    },

    get vatAmount() {
      if (!this.vatEnabled) return 0;
      const rate = Number(this.vatRate || 0);
      if (rate <= 0) return 0;
      return round2(this.subtotal * rate);
    },

    get total() {
      return round2(this.subtotal + this.vatAmount - money(this.discount));
    },

    // ✅ formatting helpers (optional but handy for x-text)
    fmt(n) {
      return round2(n).toFixed(2);
    },

    fmtQty(n) {
      const x = Number(n || 0);
      return Number.isFinite(x) ? x.toString() : '0';
    },

    badgeClass(t) {
      const s = normalizeType(t);
      if (s === 'labour') return 'bg-emerald-50 text-emerald-800';
      if (s === 'parts') return 'bg-indigo-50 text-indigo-800';
      if (s === 'materials') return 'bg-amber-50 text-amber-800';
      if (s === 'sublet') return 'bg-slate-100 text-slate-700';
      return 'bg-slate-100 text-slate-700';
    },

    // ✅ Inline edit helpers (used by your Blade)
    startEdit(i) {
      this.editingIndex = Number(i);
    },

    toggleVat() {
      if (!this.editable) return;
      this.vatEnabled = !this.vatEnabled;
    },

    stopEdit() {
      this.editingIndex = null;
    },

    // ✅ Quick Add (matches your Blade addQuick())
    addQuick() {
      if (!this.editable) return;

      const description = String(this.quick.description || '').trim();
      if (!description.length) return;

      const qty = money(this.quick.qty || 1);
      const amount = money(this.quick.amount || 0);

      // Amount is the line total (Dalima style). Derive unit_price for internal consistency.
      const unit_price = qty > 0 ? round2(amount / qty) : 0;

      this.lines.push({
        type: normalizeType(this.quick.type || 'labour'),
        description,
        qty,
        unit_price,
        amount: round2(amount),
      });

      // keep type, reset rest
      this.quick.description = '';
      this.quick.qty = 1;
      this.quick.amount = 0;

      showToast(this, 'ok', 'Line added.');
    },

    // ✅ Back-compat aliases (Blade expects these names)
    addQuickLine() {
      return this.addQuick();
    },

    addBlankLine() {
      if (!this.editable) return;

      const qty = money(1);
      const amount = money(0);

      this.lines.push({
        type: 'labour',
        description: '',
        qty,
        unit_price: 0,
        amount,
      });

      // start editing the new row
      this.editingIndex = this.lines.length - 1;

      showToast(this, 'ok', 'Line added.');
    },

    addPresetLine(preset = {}) {
      if (!this.editable) return;

      const type = normalizeType(preset.type || 'labour');
      const description = String(preset.description || '').trim();
      const qty = money(preset.qty ?? 1);
      const unit_price = money(preset.unit_price ?? 0);

      this.lines.push({
        type,
        description,
        qty,
        unit_price,
        amount: computeLineAmount({ qty, unit_price }),
      });

      showToast(this, 'ok', 'Line added.');
    },

    focusQuickDesc() {
      // matches your Blade: id="quoteQuickDesc"
      queueMicrotask(() => {
        const el = document.getElementById('quoteQuickDesc');
        if (el) el.focus();
      });
    },

    // ✅ Patch line fields live (matches your $dispatch('quotation:patch-line', ...))
    patchLine(index, field, value) {
      if (!this.editable) return;

      const i = Number(index);
      if (!Number.isFinite(i) || !this.lines[i]) return;

      if (field === 'type') {
        this.lines[i].type = normalizeType(value);
      } else if (field === 'qty') {
        // Dalima style: keep line total stable, derive unit from amount
        const newQty = money(value || 0);
        this.lines[i].qty = newQty;

        const amt = money(this.lines[i].amount || 0);
        this.lines[i].unit_price = newQty > 0 ? round2(amt / newQty) : 0;
        return;
      } else if (field === 'amount') {
        const amt = money(value || 0);
        this.lines[i].amount = round2(amt);

        const qty = money(this.lines[i].qty || 0);
        this.lines[i].unit_price = qty > 0 ? round2(amt / qty) : 0;
        return;
      } else if (field === 'unit_price') {
        // keep for backwards compatibility if any old UI still sends it
        this.lines[i].unit_price = money(value || 0);
      } else if (field === 'description') {
        this.lines[i].description = String(value || '');
      } else {
        this.lines[i][field] = value;
      }

      // default recompute (for legacy paths)
      const qty = money(this.lines[i].qty || 0);
      const unit_price = money(this.lines[i].unit_price || 0);
      this.lines[i].amount = computeLineAmount({ qty, unit_price });
    },

    // --- modal ops (kept; your Blade currently uses dispatch events for add/edit)
    openAdd() {
      if (!this.editable) return;
      this.modal.open = true;
      this.modal.mode = 'add';
      this.modal.index = null;
      this.modal.type = 'labour';
      this.modal.description = '';
      this.modal.qty = 1;
      this.modal.unit_price = 0;
    },

    openEdit(index) {
      if (!this.editable) return;
      if (index === null || index === undefined) return;
      const i = Number(index);
      if (!Number.isFinite(i) || !this.lines[i]) return;

      const l = this.lines[i];
      this.modal.open = true;
      this.modal.mode = 'edit';
      this.modal.index = i;
      this.modal.type = normalizeType(l.type);
      this.modal.description = String(l.description || '');
      this.modal.qty = money(l.qty || 1);
      this.modal.unit_price = money(l.unit_price || 0);
    },

    closeModal() {
      this.modal.open = false;
    },

    saveModalLine() {
      if (!this.editable) return;

      const type = normalizeType(this.modal.type);
      const description = String(this.modal.description || '').trim();
      const qty = money(this.modal.qty || 0);
      const unit_price = money(this.modal.unit_price || 0);

      if (!description.length) {
        showToast(this, 'error', 'Description is required.');
        return;
      }
      if (qty <= 0) {
        showToast(this, 'error', 'Qty must be greater than 0.');
        return;
      }

      const line = {
        type,
        description,
        qty,
        unit_price,
        amount: computeLineAmount({ qty, unit_price }),
      };

      if (this.modal.mode === 'edit' && this.modal.index !== null && this.lines[this.modal.index]) {
        this.lines.splice(this.modal.index, 1, line);
        showToast(this, 'ok', 'Line updated.');
      } else {
        this.lines.push(line);
        showToast(this, 'ok', 'Line added.');
      }

      this.closeModal();
    },

    deleteLine(index) {
      if (!this.editable) return;
      const i = Number(index);
      if (!Number.isFinite(i) || !this.lines[i]) return;

      if (!confirm('Remove this line?')) return;
      this.lines.splice(i, 1);
      showToast(this, 'ok', 'Line removed.');
    },

    // --- persistence (optional endpoints)
    async saveDraft() {
      if (!this.editable) return;
      if (!this.saveUrl) {
        showToast(this, 'ok', 'Draft saved (UI only). Backend wiring coming next.');
        return;
      }

      if (this.saving) return;
      this.saving = true;

      try {
        const payload = {
          job_id: this.jobId,
          action: 'save',
          lines: this.lines,
          tax: this.tax,
          discount: this.discount,
        };

        if (!this.jobId) {
          showToast(this, 'error', 'Missing job id (jobId not passed to quotation module).');
          this.saving = false;
          return;
        }

        const res = await fetch(this.saveUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrf,
          },
          body: JSON.stringify(payload),
          credentials: 'same-origin',
        });

        const json = await res.json().catch(() => ({}));
        if (!res.ok || json?.ok === false) throw json;

        this.lastSavedAt = new Date().toISOString();
        showToast(this, 'ok', 'Draft saved.');
      } catch (e) {
        console.error(e);
        showToast(this, 'error', e?.message || 'Failed to save draft.');
      } finally {
        this.saving = false;
      }
    },

    // ✅ DROP-IN PATCH: submitQuote fixed
    async submitQuote() {
      if (!this.editable) return;

      if (this.lines.length < 1) {
        showToast(this, 'error', 'Add at least one quotation line first.');
        return;
      }

      // ✅ We don’t have a separate submit route; submit uses save endpoint with action=submit
      const url = this.submitUrl || this.saveUrl;
      if (!url) {
        showToast(this, 'error', 'Missing saveUrl/submitUrl.');
        return;
      }

      if (!this.jobId) {
        showToast(this, 'error', 'Missing job id (jobId not passed to quotation module).');
        return;
      }

      if (this.submitting) return;
      this.submitting = true;

      try {
        const payload = {
          job_id: this.jobId,
          action: 'submit',
          lines: this.lines,
          tax: this.tax,
          discount: this.discount,
        };

        // ✅ match saveDraft() fetch shape (prevents HTML redirects / session issues)
        const res = await fetch(url, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': this.csrf,
          },
          body: JSON.stringify(payload),
          credentials: 'same-origin',
        });

        // Try parse JSON; if backend returned HTML, surface it clearly
        const data = await res.json().catch(() => ({}));

        if (!res.ok || data?.ok === false) {
          const msg =
            data?.message ||
            data?.error ||
            (res.status ? `Submit failed (HTTP ${res.status}).` : 'Submit failed.');
          showToast(this, 'error', msg);
          return;
        }

        // ✅ DO NOT assign to getter properties (subtotal/total are getters and would throw)
        // If server returns any canonical fields, update only writable properties:
        if (typeof data.tax === 'number') this.tax = data.tax;
        if (typeof data.discount === 'number') this.discount = data.discount;

        this.status = String(data.status || 'submitted');

        // lock UI edits after submit
        this.editable = false;

        showToast(this, 'ok', 'Quotation submitted.');

        // optional: inform other modules (approval rehydrate can listen to this)
        window.dispatchEvent(
          new CustomEvent('quotation:submitted', { detail: { jobId: this.jobId, status: this.status } })
        );
      } catch (e) {
        console.error(e);
        // show real error message if present (instead of always "Network error")
        showToast(this, 'error', e?.message || 'Network error while submitting.');
      } finally {
        this.submitting = false;
      }
    },
  };
};

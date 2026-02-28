{{-- Vault Picker (modal index) --}}
@php
    $q = $q ?? '';
@endphp
<script>
window.VAULT_CONTEXT = {
  mode: @json($attachMode ?? 'inspection'),
  attachUrl: @json($attachAction ?? route('jobs.insurance.vault.attach', $job->id)),
  returnUrl: @json($returnUrl ?? null),
  csrf: @json(csrf_token()),
};
</script>
<div class="space-y-3" data-vault-picker>

    {{-- Search + Upload Row --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-2">
        <div class="flex-1">
            <input type="text"
                   class="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm"
                   placeholder="Search vault…"
                   value="{{ e($q) }}"
                   data-vault-search>
        </div>

        <form class="flex items-center gap-2"
              method="POST"
              enctype="multipart/form-data"
              action="{{ route('jobs.insurance.vault.upload', ['job' => $job->id]) }}"
              data-vault-upload-form>
            @csrf

            <input type="file"
                   name="photo"
                   accept="image/*"
                   data-vault-upload-input
                   class="block w-full text-sm border border-slate-200 rounded-lg px-3 py-2" />

            <button type="submit"
                    class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800">
                Upload
            </button>
        </form>
    </div>

    {{-- Grid --}}
    @includeIf('jobs.insurance.components.grid', ['items' => $items])

</div>
<script>
async function __vaultSelect(item) {

const ctx = window.VAULT_CONTEXT || {};

  // ✅ If we’re in completion picker mode, don’t do single-attach.
  // Just toggle selection so the “Attach Selected” bar/count works.
  if ((ctx.mode || 'inspection') === 'completion') {
    const id = parseInt(item?.id, 10);
    if (!id) return;
    window.dispatchEvent(new CustomEvent('vault:toggle', { detail: { id, url: item?.url || '' } }));
    return;
  }
  const ctx = window.VAULT_CONTEXT || {};
  if (!ctx.attachUrl) {
    console.warn('No attachUrl configured');
    return;
  }

  try {
    const res = await fetch(ctx.attachUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': ctx.csrf,
        'Accept': 'application/json',
      },
      body: JSON.stringify({
        media_item_ids: [item.id],
        label: ctx.mode || 'inspection',
        return: ctx.returnUrl || null,
      }),
    });

    const json = await res.json().catch(() => ({}));

    if (!res.ok || json.ok === false) {
      console.error('Attach failed', json);
      alert(json.message || 'Failed to attach photo.');
      return;
    }

    // Toggle selection UI (optional)
    const btn = document.querySelector(`.vault-thumb[data-media-id="${item.id}"]`);
    if (btn) btn.classList.toggle('is-selected');

    // Notify the inspection card (your inspection blade listens for this)
    window.dispatchEvent(new CustomEvent('inspection:photos-updated', {
      detail: {
        attached: json.attached ?? json.data?.attached ?? null,
        photosCount: json.photosCount ?? json.data?.photosCount ?? json.photos_count ?? json.data?.photos_count ?? null,
      }
    }));

  } catch (e) {
    console.error(e);
    alert('Network error attaching photo.');
  }
}
</script>
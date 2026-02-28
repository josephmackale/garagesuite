{{-- Insurance Inspection Card (Compact UI) --}}
@php
    // ✅ Ensure ctx always has job id on insurance show page
    // ✅ Step3 endpoints require ?draft=UUID (not ?job=ID). On insuranceShow, resolve draft UUID for this job.
    if (!isset($ctx) || !is_array($ctx)) {
        $ctx = [];
    }

    if (empty($ctx['draft'])) {
        $ctx['draft'] = (string) \App\Models\JobDraft::query()
            ->where('garage_id', auth()->user()->garage_id)
            ->where('job_id', $job->id)
            ->latest('id')
            ->value('draft_uuid');
    }

    // ✅ fallback (won't crash route() even if draft missing)
    $ctx['draft'] = $ctx['draft'] ?: (string) request()->query('draft', '');

    $locked         = (bool) ($locked ?? false);

    $minPhotos      = (int) ($minPhotos ?? 4);
    $totalItems     = (int) ($totalItems ?? 58);

    // If controller passes these, great. Otherwise default to 0.
    $photosCount    = (int) ($photosCount ?? (is_array($attached ?? null) ? count($attached) : 0));
    $doneItems      = (int) ($doneItems ?? ($checklistDone ?? 0));

    // Progress includes photos(min) + checklist
    $photoRatio  = $minPhotos > 0 ? min($photosCount, $minPhotos) / $minPhotos : 0;
    $listRatio   = $totalItems > 0 ? min($doneItems, $totalItems) / $totalItems : 0;

    $wPhotos     = 0.40;
    $wChecklist  = 0.60;

    $progressPct = (int) round((($photoRatio * $wPhotos) + ($listRatio * $wChecklist)) * 100);
    $progressPct = max(0, min(100, $progressPct));

    if ($locked) {
        $statusLabel = 'Completed';
        $statusTone  = 'bg-green-50 text-green-700 border-green-200';
    } elseif ($photosCount > 0 || $doneItems > 0) {
        $statusLabel = 'In progress';
        $statusTone  = 'bg-yellow-50 text-yellow-800 border-yellow-200';
    } else {
        $statusLabel = 'Not started';
        $statusTone  = 'bg-gray-50 text-gray-700 border-gray-200';
    }

    $photosOk = $photosCount >= $minPhotos;

    // ✅ Always carry draft context into Step-3 endpoints (wizard-backed)
    $draft = (string) ($ctx['draft'] ?? '');

    $saveUrl     = route('jobs.insurance.inspection.save', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $completeUrl = route('jobs.insurance.inspection.complete', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $vaultUrl = route('jobs.insurance.vault', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $attachUrl = route('jobs.insurance.vault.attach', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $detachUrl = route('jobs.insurance.vault.detach', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $checklistLoadUrl = route('jobs.insurance.inspection.checklist', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);

    $checklistSaveUrl = route('jobs.insurance.inspection.checklist.save', [
        'job'   => $job->id,
        'draft' => $draft,
    ]);


@endphp


<div
  id="insurance-inspection"
  class="scroll-mt-24 w-full bg-white rounded-lg border border-gray-200 px-4 py-3"
  x-data="insuranceInspectionCard({
      saveUrl: @js($saveUrl),
      completeUrl: @js($completeUrl),
      vaultUrl: @js($vaultUrl),
      attachUrl: @js($attachUrl),
      jobId: @js($job->id),
      checklistLoadUrl: @js($checklistLoadUrl),
      checklistSaveUrl: @js($checklistSaveUrl),
      detachUrl: @js($detachUrl),

      // ✅ DB truth on refresh
      status: @js($inspection?->status ?? 'draft'),
      completed: @js(($inspection?->status ?? null) === 'completed'),
      locked: @js(($inspection?->status ?? null) === 'completed'),

      minPhotos: @js($minPhotos),
      totalItems: @js($totalItems),
      doneCount: @js($doneItems),

      attached: @js($attached ?? []),
      photosCount: @js(is_array($attached ?? null) ? count($attached) : 0),

      checklistItems: @js(config('inspection_checklists.dalima_checkin_checkout_v1') ?? [])
  })"
>


    {{-- Top row --}}
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-semibold text-gray-900">
                    Insurance Inspection
                </h3>

                {{-- ✅ Alpine-driven status badge (updates after markComplete) --}}
                <span
                    class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-md border"
                    :class="locked
                      ? 'bg-green-50 text-green-700 border-green-200'
                      : ((photosCount > 0 || doneItems > 0)
                          ? 'bg-yellow-50 text-yellow-800 border-yellow-200'
                          : 'bg-gray-50 text-gray-700 border-gray-200')"
                    x-text="locked ? 'Completed' : ((photosCount > 0 || doneItems > 0) ? 'In progress' : 'Not started')"
                ></span>

                {{-- ✅ Alpine-driven locked pill --}}
                <span
                    x-show="locked"
                    x-cloak
                    class="inline-flex items-center px-2 py-0.5 text-[11px] font-medium rounded-md border bg-gray-50 text-gray-700 border-gray-200"
                >
                    Locked
                </span>
            </div>

            <div class="mt-0.5 text-xs text-gray-500">
                Photos (min {{ $minPhotos }}) + checklist ({{ $totalItems }} items).
            </div>

            {{-- ✅ Toast renderer so you SEE 422 validation errors --}}
            <div
                x-cloak
                x-show="toast.show"
                class="mt-2 text-xs rounded-md border px-3 py-2"
                :class="toast.type === 'error'
                    ? 'bg-rose-50 border-rose-200 text-rose-700'
                    : 'bg-emerald-50 border-emerald-200 text-emerald-700'"
            >
                <span x-text="toast.message"></span>
            </div>
        </div>

        {{-- Compact actions --}}
        <div class="flex items-center gap-2 shrink-0">
            <button type="button"
              class="px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-300 bg-white hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="locked"
              @click="openPhotos = !openPhotos; if(openPhotos) openChecklist = false"
            >
              Photos
            </button>

            <button type="button"
              class="px-2.5 py-1.5 text-xs font-medium rounded-md border border-gray-900 bg-gray-900 text-white hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="locked"
              @click="openChecklist = !openChecklist;
              if (openChecklist) {
                openPhotos = false;
                if (!checklistLoaded) loadChecklist();
              }"
            >
              Checklist
            </button>
        </div>
    </div>

    {{-- Thin progress + compact stats --}}
    <div class="mt-3">
        <div class="flex items-center justify-between text-[11px] text-gray-500">
            <div class="flex items-center gap-3">
                <span class="{{ $photosOk ? 'text-green-700' : 'text-gray-600' }}">
                    Photos: <span class="font-medium text-gray-900" x-text="(attached?.length || 0)"></span>/{{ $minPhotos }}
                </span>
                <span>
                    Checklist: <span x-text="Number(doneItems ?? 0)"></span>/<span x-text="Number(totalItems ?? 0)"></span>
                </span>
            </div>
            <div class="font-medium text-gray-700">
                <span x-text="progressPctLabel + '%'"></span>
            </div>
        </div>

        <div class="mt-2 h-1.5 w-full rounded-full bg-gray-100 overflow-hidden">
            <div class="h-full rounded-full bg-gray-900" :style="`width:${progressPctLabel}%`"></div>
        </div>

        {{-- Optional: tiny “requirement hint” only when not satisfied --}}
        @if(!$locked && !$photosOk)
            <div class="mt-2 text-[11px] text-gray-500" x-show="!locked && (attached?.length || 0) < {{ $minPhotos }}">
                Add <span class="font-medium text-gray-800" x-text="{{ $minPhotos }} - (attached?.length || 0)"></span>
                more photo(s) to meet minimum.
            </div>
        @endif
    </div>

    {{-- Photos panel (collapsible) --}}
    <div x-cloak x-show="openPhotos" x-transition class="mt-3 pt-3 border-t border-gray-200">
      <div class="flex items-center justify-between">
        <div class="text-xs font-medium text-gray-800">Inspection Photos</div>
        <div class="text-[11px] text-gray-500">
          Minimum: {{ $minPhotos }} • Attached: {{ $photosCount }}
        </div>
      </div>

      {{-- Thumbnails preview (max 4) --}}
      <div class="mt-2 flex items-center gap-2">

          <!-- Preview grid -->
          <div class="grid grid-cols-4 sm:grid-cols-6 gap-2 flex-1">

              <template x-if="!attached || attached.length === 0">
                  <div class="col-span-full text-[11px] text-gray-500">
                      No photos attached yet.
                  </div>
              </template>

              <!-- Show only first 4 -->
            <template x-for="(m, idx) in (attached || []).slice(0,4)" :key="m.attachment_id || m.id || idx">
              <div class="relative group aspect-square rounded-md overflow-hidden border border-gray-200 bg-gray-50">
                <a :href="m.url" target="_blank" rel="noopener" class="block w-full h-full">
                  <img :src="m.thumb_url || m.url" class="w-full h-full object-cover" alt="">
                </a>

                <button type="button"
                  class="absolute top-1 right-1 hidden group-hover:flex items-center justify-center
                        w-6 h-6 rounded bg-black/70 text-white text-xs"
                  :disabled="locked || detachingId === (m.attachment_id || m.id)"
                  @click.prevent="detachPhoto(m)"
                  title="Remove photo"
                >
                  <span x-show="detachingId !== (m.attachment_id || m.id)">✕</span>
                  <span x-show="detachingId === (m.attachment_id || m.id)">…</span>
                </button>
              </div>
            </template>

          </div>

          <!-- View all button -->
          <template x-if="attached.length > 4">
              <button type="button"
                class="px-2 py-1 text-[11px] rounded border border-gray-300 hover:bg-gray-50 shrink-0"
                @click="openAllPhotos = true"
              >
                View all (<span x-text="attached.length"></span>)
              </button>
          </template>

      </div>

      <div class="mt-3 flex items-center justify-between">
        <div class="text-[11px] text-gray-500">
          Minimum: {{ $minPhotos }} • Attached: <span x-text="photosCount"></span>
        </div>

        <button type="button"
          class="px-3 py-1.5 text-xs font-medium rounded-md bg-gray-900 text-white hover:bg-black disabled:opacity-50 disabled:cursor-not-allowed"
          :disabled="locked"
          @click="openVault()"
        >
          Add photos
        </button>
      </div>
    </div>

    {{-- Checklist panel (Chevron grouped) --}}
    <div x-cloak x-show="openChecklist" x-transition class="mt-3 pt-3 border-t border-gray-200">

      <div class="flex items-center justify-between">
        <div class="text-xs font-medium text-gray-800">Inspection Checklist</div>
        <div class="text-[11px] text-gray-500">
          Done: <span class="font-semibold text-gray-900" x-text="doneItems"></span>/{{ $totalItems }}
        </div>
      </div>

      <div class="mt-2 space-y-2">

        <template x-for="sec in sections" :key="sec.key">
          <div class="rounded-md border border-gray-200 overflow-hidden">

            {{-- Section header (chevron) --}}
            <button type="button"
              class="w-full flex items-center justify-between px-3 py-2 bg-gray-50 hover:bg-gray-100"
              @click="sec.open = !sec.open"
            >
              <div class="text-[12px] font-semibold text-gray-800" x-text="sec.title"></div>

              <svg class="w-4 h-4 text-gray-500 transition-transform"
                  :class="sec.open ? 'rotate-180' : ''"
                  viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd"
                  d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.24a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z"
                  clip-rule="evenodd" />
              </svg>
            </button>

            {{-- Section body --}}
            <div x-show="sec.open" x-transition class="p-3 bg-white max-h-[320px] overflow-auto space-y-2">

              <template x-for="id in Array.from({length: sec.to - sec.from + 1}, (_,i)=> sec.from + i)" :key="id">
                <div class="rounded-md border border-gray-200 p-2">

                  <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                      <div class="text-[12px] font-medium text-gray-900">
                        <span class="text-gray-500" x-text="id + '.'"></span>
                        <span x-text="checklistItems[id]"></span>
                      </div>
                    </div>

                    <div class="flex items-center gap-1 shrink-0">
                      <button type="button"
                        class="px-2 py-1 text-[11px] rounded border"
                        :disabled="locked"
                        :class="(checklist[String(id)]?.status === 'ok') ? 'bg-green-600 text-white border-green-600' : 'bg-white border-gray-300'"
                        @click="setStatus(id,'ok')"
                      >OK</button>

                      <button type="button"
                        class="px-2 py-1 text-[11px] rounded border"
                        :disabled="locked"
                        :class="(checklist[String(id)]?.status === 'damaged') ? 'bg-yellow-500 text-white border-yellow-500' : 'bg-white border-gray-300'"
                        @click="setStatus(id,'damaged')"
                      >Damaged</button>

                      <button type="button"
                        class="px-2 py-1 text-[11px] rounded border"
                        :disabled="locked"
                        :class="(checklist[String(id)]?.status === 'missing') ? 'bg-red-600 text-white border-red-600' : 'bg-white border-gray-300'"
                        @click="setStatus(id,'missing')"
                      >Missing</button>
                    </div>
                  </div>

                  <div class="mt-2">
                    <textarea
                      class="w-full text-[11px] rounded border border-gray-200 px-2 py-1"
                      rows="2"
                      placeholder="Optional notes…"
                      :disabled="locked"
                      x-model="checklist[String(id)].note"
                      @input.debounce.250ms="recalcDone()"
                    ></textarea>
                  </div>

                </div>
              </template>

            </div>
          </div>
        </template>

      </div>

      <div class="mt-3 text-[11px] text-gray-500">
        Tip: click a status (OK/Damaged/Missing) to count the item as done.
      </div>

      {{-- Actions --}}
      <div class="mt-4 flex justify-end gap-2 border-t pt-3">

          {{-- Save Draft --}}
          <button type="button"
              class="px-3 py-1.5 text-xs font-medium rounded-md border border-gray-300
                    bg-white hover:bg-gray-50 disabled:opacity-50"
              :disabled="locked || savingChecklist"
              @click="saveChecklist()"
          >
              <span x-show="!savingChecklist">Save Draft</span>
              <span x-show="savingChecklist">Saving…</span>
          </button>

          {{-- Mark Complete --}}
          <button type="button"
            class="px-3 py-1.5 text-xs font-medium rounded-md bg-green-600 text-white hover:bg-green-700 disabled:opacity-50"
            :disabled="locked || completing || (attached?.length || 0) < {{ $minPhotos }} || (doneItems ?? 0) < {{ $totalItems }}"
            @click.prevent="markComplete()"
          >
            <span x-show="!completing">Mark Complete</span>
            <span x-show="completing">Completing…</span>
          </button>

      </div>

    </div>

    {{-- All Photos Modal --}}
    <div x-cloak x-show="openAllPhotos"
        class="fixed inset-0 z-50 flex items-center justify-center"
        x-data="{
            attached: @js($inspection_photos ?? ($attached ?? [])),
            detachingId: null,
            locked: @js(($inspection?->status ?? null) === 'completed'),
            csrf: @js(csrf_token()),
            detachUrl: @js($detachUrl),
            inspectionId: @js($inspection?->id),

            async detachPhoto(row) {
                if (this.locked) return;

                const mediaItemId  = typeof row === 'object' ? (row.id ?? null) : row;
                const attachmentId = typeof row === 'object' ? (row.attachment_id ?? row.id ?? null) : null;

                if (!mediaItemId && !attachmentId) return;

                this.detachingId = attachmentId || mediaItemId;

                try {
                    const res = await fetch(this.detachUrl, {
                        method: 'POST',
                        credentials: 'same-origin',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrf,
                        },
                        body: JSON.stringify({
                            inspection_id: this.inspectionId,
                            media_item_id: mediaItemId,
                            attachment_id: attachmentId,
                        }),
                    });


                    const json = await res.json();

                    // ✅ Support both response shapes:
                    //  A) { ok:true, attached:[...], photosCount:.. }
                    //  B) { ok:true, data:{ attached:[...], photosCount:.. } }
                    const data = (json && typeof json === 'object' && json.data && typeof json.data === 'object')
                        ? { ...json, ...json.data }
                        : json;

                    if (!res.ok || !data.ok) throw data;

                    if (Array.isArray(data.attached)) {
                        this.attached = data.attached;
                    } else {
                        this.attached = (this.attached || []).filter(p => p.id !== mediaItemId);
                    }

                    window.dispatchEvent(new CustomEvent('inspection:photos-updated', {
                        detail: {
                            photosCount: data.photosCount ?? data.photos_count ?? (this.attached?.length || 0),
                            attached: this.attached,
                        }
                    }));

                } catch (e) {
                    console.error(e);
                    alert(e?.message || 'Failed to remove photo');
                } finally {
                    this.detachingId = null;
                }
            }
        }">


        <div class="absolute inset-0 bg-black/40"
            @click="openAllPhotos=false"></div>

        <div class="relative bg-white w-[95vw] max-w-4xl rounded-lg shadow border">

            <div class="px-4 py-3 border-b flex items-center justify-between">
                <div class="text-sm font-semibold">
                    Inspection Photos
                    <span class="text-xs text-gray-500" x-text="'(' + (attached?.length || 0) + ')'"></span>
                </div>
                <button class="text-gray-500 hover:text-gray-800"
                        type="button"
                        @click="openAllPhotos=false">✕</button>
            </div>

            <div class="p-4 grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-3">

                <template x-if="!attached || attached.length === 0">
                    <div class="col-span-full text-sm text-gray-500">
                        No photos attached yet.
                    </div>
                </template>

                <template x-for="m in attached" :key="m.attachment_id || m.id">
                    <div class="relative group rounded border overflow-hidden">
                        <a :href="m.url" target="_blank" class="block">
                            <img :src="m.thumb_url || m.url" class="w-full h-24 object-cover">
                        </a>

                        <button type="button"
                                class="absolute top-1 right-1 hidden group-hover:flex items-center justify-center
                                      w-6 h-6 rounded bg-black/70 text-white text-xs"
                                :disabled="locked || detachingId === (m.attachment_id || m.id)"
                                @click.prevent="detachPhoto(m)"
                                title="Remove photo">
                            <span x-show="detachingId !== (m.attachment_id || m.id)">✕</span>
                            <span x-show="detachingId === (m.attachment_id || m.id)">…</span>
                        </button>
                    </div>
                </template>

            </div>

        </div>
    </div>


    {{-- Vault Modal --}}
    <div x-cloak x-show="vault.open" class="fixed inset-0 z-50 flex items-center justify-center">
      <div class="absolute inset-0 bg-black/40" @click="closeVault()"></div>

      <div class="relative bg-white w-[95vw] max-w-5xl rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
          <div class="text-sm font-semibold text-gray-900">Select photos from Vault</div>
          <button type="button" class="text-gray-500 hover:text-gray-800" @click="closeVault()">✕</button>
        </div>

        <div class="p-4">
          <div x-show="vault.loading" class="text-sm text-gray-500">Loading vault…</div>
          <div x-show="!vault.loading" x-html="vault.html"></div>

          {{-- footer INSIDE panel --}}
          <div class="mt-3 flex items-center justify-between border-t border-gray-200 pt-3">
            <div class="text-xs text-slate-500">
              Selected:
              <span class="font-semibold text-slate-900" x-text="vault.selectedIds.length"></span>
            </div>

            <button type="button"
              class="px-3 py-1.5 text-xs font-medium rounded-md bg-slate-900 text-white hover:bg-black disabled:opacity-50"
              data-vault-attach-selected
              :disabled="vault.attaching || vault.selectedIds.length === 0"
              @click="attachSelected()"
            >
              <span x-show="!vault.attaching">Attach selected</span>
              <span x-show="vault.attaching">Attaching…</span>
            </button>
          </div>

        </div>
      </div>
    </div>
    {{-- Footer (Phase CTA / status) --}}
    <div class="mt-4 pt-3 border-t border-gray-200 flex items-center justify-between gap-3">
      <div class="text-[11px] text-gray-500">
        <template x-if="!locked">
          <span>Complete inspection to unlock quotation.</span>
        </template>
        <template x-if="locked">
          <span class="text-green-700 font-medium">Inspection complete — quotation unlocked.</span>
        </template>
      </div>
    </div>
 
</div>


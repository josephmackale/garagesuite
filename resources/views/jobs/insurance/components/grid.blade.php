{{-- resources/views/jobs/create/partials/grid.blade.php (FULL DROP-IN)
    ✅ Compact grid
    ✅ Tile is clickable (button)
    ✅ Selection-ready via:
       - .vault-thumb
       - data-media-id
       - data-media-url
    ✅ JS should toggle .is-selected on the button (delegated handler recommended)
--}}

@php
    $fallbackDisk = 'public';
@endphp

<div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-2">
    @forelse($items as $it)
        @php
            $disk = $it->disk ?? $fallbackDisk;
            $url  = \Illuminate\Support\Facades\Storage::disk($disk)->url($it->path);
            $name = $it->original_name ?? ('Media #' . $it->id);
        @endphp

        <button
            type="button"
            class="vault-thumb group relative w-full text-left rounded-lg border border-slate-200 bg-white overflow-hidden hover:border-slate-300 focus:outline-none focus:ring-2 focus:ring-slate-900/20"
            data-media-id="{{ $it->id }}"
            title="{{ e($name) }}"
            onclick="__vaultSelect({ id: {{ $it->id }}, url: @js($url) })"
        >

            {{-- Thumb --}}
            <div class="h-24 bg-slate-100 overflow-hidden">
                <img
                    src="{{ $url }}"
                    alt="{{ e($name) }}"
                    class="w-full h-full object-cover transition-transform duration-200 group-hover:scale-[1.03]"
                    loading="lazy"
                >
            </div>

            {{-- Meta --}}
            <div class="p-1.5">
                <div class="text-[11px] font-semibold text-slate-900 truncate leading-tight">
                    {{ $name }}
                </div>
                <div class="text-[10px] text-slate-500 leading-tight">
                    #{{ $it->id }}
                </div>
            </div>

            {{-- Selected overlay (JS toggles .is-selected on button) --}}
            <div class="vault-selected-overlay pointer-events-none absolute inset-0 opacity-0 transition-opacity">
                <div class="absolute inset-0 bg-emerald-500/10"></div>

                <div class="absolute top-2 right-2 w-7 h-7 rounded-full bg-emerald-600 text-white flex items-center justify-center shadow text-sm">
                    ✓
                </div>
            </div>
        </button>
    @empty
        <div class="col-span-full text-sm text-slate-500">
            No images found.
        </div>
    @endforelse
</div>

<div class="mt-3 text-xs text-slate-500">
    Tip: click multiple images to select more than one.
</div>

<style>
    /* Selection visuals */
    .vault-thumb.is-selected {
        border-color: rgb(16 185 129); /* emerald-500 */
        box-shadow: 0 0 0 2px rgba(16,185,129,.25);
    }
    .vault-thumb.is-selected .vault-selected-overlay {
        opacity: 1;
    }
</style>
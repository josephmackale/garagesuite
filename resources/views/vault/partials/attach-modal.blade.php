@php
    /**
     * Required:
     *  - $attachRoute (string) e.g. route('jobs.insurance.vault.attach', $job)
     *  - $vaultItems  (\Illuminate\Pagination\LengthAwarePaginator|\Illuminate\Support\Collection)
     * Optional:
     *  - $title (string)
     */
    $title = $title ?? 'Attach from Photo Vault';

    $thumbUrlFor = function($item) {
        $thumbPath = str_replace('/image.jpg', '/thumb.jpg', $item->path);
        $thumbUrl  = \Illuminate\Support\Facades\Storage::disk($item->disk)->url($thumbPath);
        $fullUrl   = \Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->path);
        return [$thumbUrl, $fullUrl];
    };
@endphp

<div
    x-data="{
        open:false,
        selected:[],
        toggle(id){
            if (this.selected.includes(id)) this.selected = this.selected.filter(x => x !== id);
            else this.selected.push(id);
        },
        clear(){ this.selected = []; }
    }"
    class="w-full"
>
    <button
        type="button"
        @click="open=true"
        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold hover:bg-slate-800"
    >
        <span>Attach from Vault</span>
    </button>

    <div x-show="open" x-cloak class="fixed inset-0 z-50">
        <div class="absolute inset-0 bg-black/40" @click="open=false"></div>

        <div class="absolute inset-x-0 top-10 mx-auto max-w-5xl px-3">
            <div class="bg-white rounded-2xl shadow-xl border overflow-hidden">

                {{-- Header --}}
                <div class="flex items-center justify-between px-5 py-4 border-b">
                    <div class="font-semibold text-slate-900">{{ $title }}</div>
                    <button type="button" class="text-slate-500 hover:text-slate-900" @click="open=false">✕</button>
                </div>

                <form method="POST" action="{{ $attachRoute }}">
                    @csrf

                    {{-- Selected IDs --}}
                    <template x-for="id in selected" :key="id">
                        <input type="hidden" name="media_item_ids[]" :value="id">
                    </template>

                    {{-- Body --}}
                    <div class="p-5">

                        {{-- Sticky action row (ALWAYS VISIBLE) --}}
                        <div class="sticky top-0 z-10 bg-white pb-4">
                            <div class="flex items-center justify-between">
                                <div class="text-sm text-slate-600">
                                    Select photos to attach. (<span x-text="selected.length"></span> selected)
                                </div>

                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="text-sm text-slate-600 hover:text-slate-900"
                                        @click="clear()"
                                    >
                                        Clear
                                    </button>

                                    <button
                                        type="submit"
                                        :disabled="selected.length === 0"
                                        class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
                                    >
                                        Attach Selected
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- Grid scroll area --}}
                        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 max-h-[60vh] overflow-y-auto pr-1">
                            @foreach($vaultItems as $item)
                                @php([$thumbUrl, $fullUrl] = $thumbUrlFor($item))
                                <button
                                    type="button"
                                    class="relative rounded-xl border overflow-hidden group text-left"
                                    :class="selected.includes({{ $item->id }}) ? 'ring-2 ring-slate-900 border-slate-900' : 'border-slate-200'"
                                    @click="toggle({{ $item->id }})"
                                >
                                    {{-- FIXED SQUARE THUMB --}}
                                    <div class="aspect-square bg-slate-100 overflow-hidden">
                                        <img
                                            src="{{ $thumbUrl }}"
                                            onerror="this.onerror=null;this.src='{{ $fullUrl }}';"
                                            class="w-full h-full object-cover group-hover:scale-105 transition"
                                            alt="{{ $item->original_name ?? 'vault image' }}"
                                            loading="lazy"
                                        >
                                    </div>

                                    <div class="p-2 text-[11px] text-slate-600 truncate">
                                        {{ $item->original_name ?? 'photo' }}
                                    </div>

                                    <div
                                        class="absolute top-2 right-2 h-6 w-6 rounded-full bg-white/95 border flex items-center justify-center text-xs font-bold"
                                        :class="selected.includes({{ $item->id }}) ? 'text-slate-900' : 'text-slate-400'"
                                    >
                                        ✓
                                    </div>
                                </button>
                            @endforeach
                        </div>

                        {{-- Paginator --}}
                        @if(method_exists($vaultItems, 'links'))
                            <div class="mt-4">
                                {{ $vaultItems->links() }}
                            </div>
                        @endif

                    </div>

                    {{-- Optional: sticky footer for small screens --}}
                    <div class="sm:hidden border-t p-3 flex items-center justify-between gap-2 bg-white">
                        <div class="text-xs text-slate-600">
                            <span x-text="selected.length"></span> selected
                        </div>
                        <button
                            type="submit"
                            :disabled="selected.length === 0"
                            class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-semibold disabled:opacity-40 disabled:cursor-not-allowed"
                        >
                            Attach
                        </button>
                    </div>

                </form>
            </div>
        </div>
    </div>
</div>
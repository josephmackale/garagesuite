{{-- resources/views/vault/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">📸 Photo Vault</h2>
            <p class="text-sm text-gray-500 mt-1">Central image library for jobs and vehicles</p>
        </div>
    </x-slot>

    @php
        $attachMode = request('attach');     // optional
        $returnUrl  = request('return');     // optional
        $isAttach   = !empty($attachMode);

        // If you ever use this vault index for attaching, set your post route name here.
        // For now, leave null (button won't render unless you set it).
        $attachRouteName = null; // e.g. 'jobs.insurance.claim.completion-photos.attach'
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Top bar --}}
            <div class="flex items-start sm:items-center justify-between gap-3 mb-6">
                <div>
                    <div class="text-sm text-slate-600">
                        {{ $isAttach ? 'Select photos to attach' : 'All uploaded photos' }}
                    </div>
                    @if($isAttach)
                        <div class="text-xs text-slate-500 mt-1">
                            Mode: <span class="font-medium">{{ $attachMode }}</span>
                        </div>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    @if($isAttach)
                        <a href="{{ $returnUrl ?: url()->previous() }}"
                           class="inline-flex items-center px-3 py-2 rounded-lg border bg-white text-sm hover:bg-slate-50">
                            Done
                        </a>
                    @else
                        <form action="{{ route('vault.upload') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <label class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-slate-900 text-white text-sm font-medium cursor-pointer hover:bg-slate-800">
                                <span>Upload</span>
                                <input type="file" name="images[]" multiple accept="image/*" class="hidden" onchange="this.form.submit()">
                            </label>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-4 rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-800">
                    <div class="font-semibold mb-1">Upload failed:</div>
                    <ul class="list-disc pl-5 space-y-1">
                        @foreach($errors->all() as $e)
                            <li>{{ $e }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Body --}}
            @if($items->count() === 0)
                <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-14 text-center">
                    <div class="mx-auto h-12 w-12 rounded-2xl bg-slate-50 flex items-center justify-center text-xl">📷</div>
                    <div class="mt-4 text-slate-900 font-semibold">No files uploaded yet.</div>
                    <div class="mt-1 text-sm text-slate-500">Upload once, then attach to jobs and vehicles.</div>
                </div>
            @else
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @foreach($items as $item)
                        @php
                            $thumbPath = str_replace('/image.jpg', '/thumb.jpg', $item->path);
                            $thumbUrl  = \Illuminate\Support\Facades\Storage::disk($item->disk)->url($thumbPath);
                            $fullUrl   = \Illuminate\Support\Facades\Storage::disk($item->disk)->url($item->path);
                        @endphp

                        <div class="bg-white border rounded-xl overflow-hidden shadow-sm group">
                            <div class="aspect-square bg-slate-100 overflow-hidden">
                                <img
                                    src="{{ $thumbUrl }}"
                                    onerror="this.onerror=null;this.src='{{ $fullUrl }}';"
                                    class="w-full h-full object-cover group-hover:scale-105 transition"
                                    alt="{{ $item->original_name ?? 'vault image' }}"
                                    loading="lazy"
                                >
                            </div>

                            <div class="p-2">
                                <div class="text-xs font-medium text-slate-800 truncate">
                                    {{ $item->original_name ?? 'photo' }}
                                </div>
                                <div class="text-[11px] text-slate-500 truncate">
                                    {{ $item->mime_type ?? 'image' }}
                                </div>

                                {{-- Optional attach UI if you wire this screen to an attach route --}}
                                @if($isAttach && $attachRouteName)
                                    <div class="mt-2 flex items-center gap-2">
                                        <form method="POST" action="{{ route($attachRouteName) }}" class="flex-1">
                                            @csrf
                                            <input type="hidden" name="media_item_id" value="{{ $item->id }}">
                                            <input type="hidden" name="attach" value="{{ $attachMode }}">
                                            <input type="hidden" name="return" value="{{ $returnUrl }}">
                                            <button type="submit"
                                                class="w-full inline-flex justify-center items-center px-2 py-1.5 text-xs rounded border bg-blue-600 text-white border-blue-600 hover:bg-blue-700">
                                                Attach
                                            </button>
                                        </form>

                                        <a href="{{ $fullUrl }}" target="_blank"
                                           class="inline-flex items-center px-2 py-1.5 text-xs rounded border bg-white hover:bg-slate-50">
                                            View
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="mt-6">
                    {{ $items->links() }}
                </div>
            @endif

            @if($isAttach)
                <div class="sticky bottom-0 mt-6 bg-white/95 backdrop-blur border-t p-3 flex justify-end">
                    <a href="{{ $returnUrl ?: url()->previous() }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white text-sm hover:bg-blue-700">
                        Done
                    </a>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>
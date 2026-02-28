{{-- resources/views/documents/index.blade.php --}}
<x-app-layout>
    @php
        // Tabs (route-based)
        $tabs = [
            ['label' => 'All',       'route' => 'documents.index',     'active' => request()->routeIs('documents.index')],
            ['label' => 'Invoices',  'route' => 'documents.invoices',  'active' => request()->routeIs('documents.invoices')],
            ['label' => 'Job Cards', 'route' => 'documents.job-cards', 'active' => request()->routeIs('documents.job-cards')],
            ['label' => 'Receipts',  'route' => 'documents.receipts',  'active' => request()->routeIs('documents.receipts')],
        ];

        $tabBase     = "inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors";
        $tabInactive = "border-transparent text-gray-500 hover:text-indigo-700 hover:border-indigo-200";
        $tabActive   = "border-indigo-600 text-indigo-700";
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @include('inventory.partials.flash') {{-- if you have documents flash partial, swap this --}}
            {{-- If you have a documents flash partial already, use:
                 @include('documents.partials.flash')
            --}}

            {{-- Tabs + Top bar --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                {{-- Tabs row --}}
                <div class="px-4 sm:px-6">
                    <div class="flex items-center gap-2 overflow-x-auto">
                        @foreach($tabs as $t)
                            <a href="{{ route($t['route'], request()->except('page')) }}"
                               class="{{ $tabBase }} {{ $t['active'] ? $tabActive : $tabInactive }}">
                                @if($t['label'] === 'All')
                                    <x-lucide-folder class="w-4 h-4" />
                                @elseif($t['label'] === 'Invoices')
                                    <x-lucide-file-text class="w-4 h-4" />
                                @elseif($t['label'] === 'Job Cards')
                                    <x-lucide-clipboard-list class="w-4 h-4" />
                                @else
                                    <x-lucide-receipt class="w-4 h-4" />
                                @endif
                                <span>{{ $t['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-gray-100"></div>

                {{-- Top bar: Search + Bulk Actions --}}
                <div class="px-4 sm:px-6 py-3">
                    <form method="GET" action="{{ url()->current() }}"
                          class="flex flex-col sm:flex-row sm:items-center gap-3">

                        {{-- Search input --}}
                        <div class="flex-1">
                            <div class="flex items-center gap-2 w-full rounded-lg border border-gray-200 bg-white px-3 py-2">
                                <x-lucide-search class="w-4 h-4 text-gray-400 shrink-0" />

                                <input
                                    name="q"
                                    value="{{ $q ?? '' }}"
                                    placeholder="Search documents…"
                                    class="w-full border-0 p-0 text-sm text-gray-900 placeholder:text-gray-400
                                           focus:ring-0 focus:outline-none"
                                />
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center gap-3 shrink-0">

                            {{-- Reset --}}
                            <a href="{{ url()->current() }}"
                               title="Reset search"
                               class="text-gray-400 hover:text-gray-900 transition">
                                <x-lucide-rotate-ccw class="w-4 h-4" />
                            </a>

                            {{-- Bulk download --}}
                            <button type="button"
                                    id="bulk-download-btn"
                                    title="Download selected as ZIP"
                                    class="text-gray-400 hover:text-gray-900 transition">
                                <x-lucide-download class="w-4 h-4" />
                            </button>

                            {{-- Bulk delete --}}
                            <button type="button"
                                    id="bulk-delete-btn"
                                    title="Delete selected documents"
                                    class="text-red-400 hover:text-red-600 transition">
                                <x-lucide-trash-2 class="w-4 h-4" />
                            </button>

                        </div>
                    </form>

                    {{-- Select all --}}
                    <div class="mt-2 text-xs text-gray-500 flex items-center gap-2">
                        <input id="select-all" type="checkbox" class="rounded border-gray-300">
                        <label for="select-all" class="select-none cursor-pointer">
                            Select all on this page
                        </label>
                    </div>
                </div>
            </div>

            {{-- List --}}
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                @if ($documents->isEmpty())
                    <div class="py-16">
                        <div class="mx-auto w-full max-w-md text-center">
                            <div class="mx-auto mb-4 h-24 w-24 rounded-full bg-gray-50 flex items-center justify-center">
                                <x-lucide-inbox class="w-10 h-10 text-gray-400" />
                            </div>
                            <div class="text-lg font-semibold text-gray-900">No data found</div>
                            <p class="mt-1 text-sm text-gray-500">
                                No documents found in this tab.
                            </p>
                        </div>
                    </div>
                @else
                    {{-- ONE selection form and submit it to different endpoints via JS --}}
                    <form id="bulk-form" method="POST">
                        @csrf

                        <ul class="divide-y divide-gray-100">
                            @foreach ($documents as $doc)
                                <li class="px-4 sm:px-6 py-3 hover:bg-gray-50 transition">
                                    <div class="flex items-center justify-between gap-4">

                                        {{-- LEFT: checkbox + title --}}
                                        <div class="flex items-center gap-3 min-w-0">
                                            <input
                                                type="checkbox"
                                                name="ids[]"
                                                value="{{ $doc->id }}"
                                                class="doc-checkbox rounded border-gray-300"
                                            />

                                            <div class="min-w-0">
                                                <div class="text-sm font-medium text-gray-900 truncate">
                                                    {{ $doc->name ?? $doc->file_name }}
                                                </div>
                                                <div class="text-xs text-gray-500 truncate">
                                                    {{ optional($doc->updated_at)->format('d M Y, H:i') }}
                                                    · {{ number_format(($doc->file_size ?? 0) / 1024, 1) }} KB
                                                </div>
                                            </div>
                                        </div>

                                        {{-- RIGHT: single actions --}}
                                        <div class="flex items-center gap-4 shrink-0">
                                            <a href="{{ route('documents.view', $doc) }}" target="_blank"
                                               class="text-gray-400 hover:text-gray-900 transition" title="View">
                                                <x-lucide-eye class="w-4 h-4" />
                                            </a>

                                            <a href="{{ route('documents.download', $doc) }}"
                                               class="text-gray-400 hover:text-gray-900 transition" title="Download">
                                                <x-lucide-download class="w-4 h-4" />
                                            </a>

                                            <form method="POST" action="{{ route('documents.destroy', $doc) }}"
                                                  onsubmit="return confirm('Remove this document from archive?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="text-red-400 hover:text-red-600 transition"
                                                        title="Delete">
                                                    <x-lucide-trash-2 class="w-4 h-4" />
                                                </button>
                                            </form>
                                        </div>

                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </form>

                    <div class="px-4 sm:px-6 py-4 border-t border-gray-100">
                        {{ $documents->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>

    <script>
        (function () {
            const selectAll = document.getElementById('select-all');
            const checkboxes = () => Array.from(document.querySelectorAll('.doc-checkbox'));
            const bulkForm = document.getElementById('bulk-form');

            function anySelected() {
                return checkboxes().some(cb => cb.checked);
            }

            if (selectAll) {
                selectAll.addEventListener('change', () => {
                    checkboxes().forEach(cb => cb.checked = selectAll.checked);
                });
            }

            // Keep select-all accurate
            document.addEventListener('change', (e) => {
                if (!e.target.classList.contains('doc-checkbox')) return;
                const all = checkboxes();
                const allChecked = all.length > 0 && all.every(cb => cb.checked);
                if (selectAll) selectAll.checked = allChecked;
            });

            document.getElementById('bulk-download-btn')?.addEventListener('click', () => {
                if (!bulkForm) return;
                if (!anySelected()) return alert('Select at least one document.');
                bulkForm.action = "{{ route('documents.bulk.download') }}";
                bulkForm.method = "POST";
                bulkForm.submit();
            });

            document.getElementById('bulk-delete-btn')?.addEventListener('click', () => {
                if (!bulkForm) return;
                if (!anySelected()) return alert('Select at least one document.');
                if (!confirm('Delete selected documents from the archive?')) return;

                // Laravel needs method spoofing for DELETE
                let method = bulkForm.querySelector('input[name="_method"]');
                if (!method) {
                    method = document.createElement('input');
                    method.type = 'hidden';
                    method.name = '_method';
                    bulkForm.appendChild(method);
                }
                method.value = 'DELETE';

                bulkForm.action = "{{ route('documents.bulk.delete') }}";
                bulkForm.method = "POST";
                bulkForm.submit();
            });
        })();
    </script>
</x-app-layout>

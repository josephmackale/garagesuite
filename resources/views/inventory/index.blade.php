{{-- resources/views/inventory/index.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        @include('inventory.partials.header')
    </x-slot>

    @php
        // active stock tab (matches screenshot)
        $stock = request('stock', 'low'); // default highlight = Low Stock (like screenshot)

        $tabs = [
            'out' => ['label' => 'Out Of Stock', 'icon' => 'package-x'],
            'low' => ['label' => 'Low Stock',    'icon' => 'alert-triangle'],
            'in'  => ['label' => 'In Stock',     'icon' => 'package-check'],
        ];

        if (!array_key_exists($stock, $tabs)) {
            $stock = 'low';
        }

        $tabBase = "inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors";
        $tabInactive = "border-transparent text-gray-500 hover:text-indigo-700 hover:border-indigo-200";
        $tabActive   = "border-indigo-600 text-indigo-700";
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">

            @include('inventory.partials.flash')

            {{-- Tabs row container (like screenshot) --}}
            <div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
                <div class="px-4">
                    <div class="flex items-center justify-between gap-4">
                        {{-- Tabs --}}
                        <div class="flex items-center gap-2 overflow-x-auto">
                            @foreach($tabs as $key => $meta)
                                <a href="{{ route('inventory-items.index', array_merge(request()->except('page'), ['stock' => $key])) }}"
                                   class="{{ $tabBase }} {{ $stock === $key ? $tabActive : $tabInactive }}">
                                    @if($meta['icon'] === 'package-x')
                                        <x-lucide-package-x class="w-4 h-4" />
                                    @elseif($meta['icon'] === 'alert-triangle')
                                        <x-lucide-alert-triangle class="w-4 h-4" />
                                    @else
                                        <x-lucide-package-check class="w-4 h-4" />
                                    @endif

                                    <span>{{ $meta['label'] }}</span>
                                </a>
                            @endforeach
                        </div>

                        {{-- Search (right side like screenshot) --}}
                        <div class="hidden md:block w-full max-w-sm">
                            @include('inventory.partials.filters', [
                                'search' => $search ?? ''
                            ])
                        </div>
                    </div>
                </div>

                {{-- Content --}}
                <div class="border-t border-gray-100">
                    {{-- MOBILE / TABLET (cards) --}}
                    <div class="lg:hidden p-4">
                        @if($items->count())
                            <div class="space-y-3">
                                @foreach($items as $item)
                                    @include('inventory.partials.card', ['item' => $item])
                                @endforeach
                            </div>
                        @else
                            {{-- Centered empty like screenshot --}}
                            <div class="py-14">
                                <div class="mx-auto w-full max-w-md text-center">
                                    <div class="mx-auto mb-4 h-20 w-20 rounded-full bg-gray-50 flex items-center justify-center">
                                        <x-lucide-inbox class="w-8 h-8 text-gray-400" />
                                    </div>
                                    <div class="text-lg font-semibold text-gray-900">No data found</div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        No items in <span class="font-medium text-gray-700">{{ $tabs[$stock]['label'] }}</span>.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- DESKTOP --}}
                    <div class="hidden lg:block">
                        @if($items->count())
                            <div class="p-4">
                                @include('inventory.partials.table', ['items' => $items])
                            </div>
                        @else
                            {{-- Centered empty like screenshot --}}
                            <div class="py-16">
                                <div class="mx-auto w-full max-w-md text-center">
                                    <div class="mx-auto mb-4 h-24 w-24 rounded-full bg-gray-50 flex items-center justify-center">
                                        <x-lucide-inbox class="w-10 h-10 text-gray-400" />
                                    </div>
                                    <div class="text-lg font-semibold text-gray-900">No data found</div>
                                    <p class="mt-1 text-sm text-gray-500">
                                        No items in <span class="font-medium text-gray-700">{{ $tabs[$stock]['label'] }}</span>.
                                    </p>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Pagination --}}
                    <div class="px-4 py-4 border-t border-gray-100">
                        {{ $items->withQueryString()->links() }}
                    </div>
                </div>
            </div>

            {{-- Mobile search below tabs --}}
            <div class="md:hidden">
                @include('inventory.partials.filters', [
                    'search' => $search ?? ''
                ])
            </div>

        </div>
    </div>
</x-app-layout>

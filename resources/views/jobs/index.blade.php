<x-app-layout>


    @php
        // Tabs (board statuses)
        $boardStatuses = [
            'pending'     => ['label' => 'Pending',     'hint' => 'New / waiting'],
            'in_progress' => ['label' => 'In Progress', 'hint' => 'Being worked on'],
            'completed'   => ['label' => 'Completed',   'hint' => 'Ready to invoice'],
            'cancelled'   => ['label' => 'Cancelled',   'hint' => 'Stopped / voided'],
        ];

        // Active tab from query
        $activeStatus = request('status', $filters['status'] ?? 'pending');
        if (!array_key_exists($activeStatus, $boardStatuses)) {
            $activeStatus = 'pending';
        }

        // Summary helpers
        $countFor  = fn($k) => (int) ($statusSummary[$k]->jobs_count ?? 0);
        $amountFor = fn($k) => (float) ($statusSummary[$k]->total_amount ?? 0);

        // Filter current jobs collection into the active tab
        $jobsForActive = $jobs->filter(function ($job) use ($activeStatus) {
            $s = $job->status ?? 'pending';
            if ($activeStatus === 'pending') {
                return in_array($s, ['pending', 'draft'], true);
            }
            return $s === $activeStatus;
        });

        // UI classes
        $tabBase = "inline-flex items-center gap-2 px-4 py-3 text-sm font-medium border-b-2 transition-colors";
        $tabInactive = "border-transparent text-gray-500 hover:text-indigo-700 hover:border-indigo-200";
        $tabActive   = "border-indigo-600 text-indigo-700";
    @endphp

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @include('jobs.partials.flash')

            {{-- Optional: keep your filters if you want search --}}
            @include('jobs.partials.filters', [
                'q' => $filters['q'] ?? '',
                'activeStatus' => $activeStatus,
                'statuses' => $statuses,
            ])

            {{-- Tabs container (like screenshot) --}}
            <div class="bg-white rounded-lg border border-gray-100 overflow-hidden">
                <div class="px-4">
                    <div class="flex items-center gap-2 overflow-x-auto">
                        @foreach($boardStatuses as $key => $meta)
                            <a href="{{ route('jobs.index', array_merge(request()->except('page'), ['status' => $key])) }}"
                               class="{{ $tabBase }} {{ $activeStatus === $key ? $tabActive : $tabInactive }}">
                                {{-- small icon --}}
                                @if($key === 'pending')
                                    <x-lucide-folder class="w-4 h-4" />
                                @elseif($key === 'in_progress')
                                    <x-lucide-loader-circle class="w-4 h-4" />
                                @elseif($key === 'completed')
                                    <x-lucide-check-check class="w-4 h-4" />
                                @else
                                    <x-lucide-x-circle class="w-4 h-4" />
                                @endif

                                <span>{{ $meta['label'] }}</span>

                                <span class="ml-1 inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                    {{ $countFor($key) }}
                                </span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <div class="border-t border-gray-100">
                    {{-- Empty state --}}
                    @if($jobsForActive->count() === 0)
                        <div class="py-14">
                            <div class="mx-auto w-full max-w-md text-center">
                                <div class="mx-auto mb-4 h-20 w-20 rounded-full bg-gray-50 flex items-center justify-center">
                                    <x-lucide-inbox class="w-8 h-8 text-gray-400" />
                                </div>
                                <div class="text-lg font-semibold text-gray-900">No data found</div>
                                <p class="mt-1 text-sm text-gray-500">
                                    There are no jobs in <span class="font-medium text-gray-700">{{ $boardStatuses[$activeStatus]['label'] }}</span>.
                                </p>

                                <div class="mt-5 flex items-center justify-center gap-2">
                                    <a href="{{ route('jobs.create.step1') }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 rounded-lg text-sm font-semibold text-white hover:bg-indigo-700">
                                        <x-lucide-plus class="w-4 h-4" />
                                        Create job
                                    </a>

                                    <a href="{{ route('jobs.index') }}"
                                       class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        View all
                                    </a>
                                </div>
                            </div>
                        </div>
                    @else
                        {{-- Table list --}}
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-gray-50 text-gray-600">
                                    <tr>
                                        <th class="px-4 py-3 text-left font-semibold">Job</th>
                                        <th class="px-4 py-3 text-left font-semibold">Customer</th>
                                        <th class="px-4 py-3 text-left font-semibold">Vehicle</th>
                                        <th class="px-4 py-3 text-left font-semibold">Created</th>
                                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @foreach($jobsForActive as $job)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3">
                                                <div class="font-semibold text-gray-900">
                                                    {{ $job->title ?? ('Job #'.$job->id) }}
                                                </div>

                                                <div class="text-xs text-gray-500 flex items-center gap-2 mt-0.5">
                                                    Status: {{ str_replace('_',' ', $job->status ?? 'pending') }}

                                                    @php
                                                        $payer = $job->payer_type ?? 'individual';

                                                        $payerLabel = match($payer) {
                                                            'insurance'  => 'Insurance',
                                                            'company'    => 'Company',
                                                            default      => 'Individual',
                                                        };

                                                        $payerClass = match($payer) {
                                                            'insurance' => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                                                            'company'   => 'bg-amber-50 text-amber-700 ring-amber-200',
                                                            default     => 'bg-slate-50 text-slate-700 ring-slate-200',
                                                        };
                                                    @endphp

                                                    <span
                                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-medium ring-1 ring-inset {{ $payerClass }}">
                                                        {{ $payerLabel }}
                                                    </span>
                                                </div>
                                            </td>


                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $job->customer->name ?? '—' }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-700">
                                                {{ $job->vehicle->registration ?? ($job->vehicle->plate ?? '—') }}
                                            </td>

                                            <td class="px-4 py-3 text-gray-500">
                                                {{ optional($job->created_at)->format('d M Y') }}
                                            </td>

                                            <td class="px-4 py-3 text-right">
                                                <a href="{{ ($job->payer_type ?? null) === 'insurance'
                                                        ? route('jobs.insurance.show', $job)
                                                        : route('jobs.show', $job) }}"
                                                   class="inline-flex items-center gap-1 text-indigo-700 font-semibold hover:underline">
                                                    {{ ($job->payer_type ?? null) === 'insurance' ? 'Open' : 'View' }}
                                                    <x-lucide-arrow-right class="w-4 h-4" />
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Pagination --}}
                        <div class="px-4 py-4 border-t border-gray-100">
                            {{ $jobs->links() }}
                        </div>
                    @endif
                </div>
            </div>

        </div>
    </div>
</x-app-layout>

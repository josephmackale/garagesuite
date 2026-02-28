<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('SMS Campaigns') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">

            {{-- Top bar --}}
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h3 class="text-lg font-medium text-gray-900">SMS Campaigns</h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Manage and review all your SMS campaigns.
                    </p>
                </div>

                <a href="{{ route('sms-campaigns.create') }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md
                          font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700
                          focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                    + New Campaign
                </a>
            </div>

            {{-- Flash --}}
            @if(session('success'))
                <div class="mb-4 rounded-md bg-green-50 p-4">
                    <div class="flex">
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">
                                {{ session('success') }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Empty state --}}
            @if($campaigns->isEmpty())
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-10 text-center">
                        <h4 class="text-base font-semibold text-gray-900">No campaigns yet</h4>
                        <p class="mt-2 text-sm text-gray-500">
                            Click <span class="font-semibold">“New Campaign”</span> to create your first one.
                        </p>
                    </div>
                </div>
            @else
                {{-- Table --}}
                <div class="bg-white shadow sm:rounded-lg overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Name
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Total Recipients
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Sent
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Created
                                </th>
                                <th scope="col"
                                    class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($campaigns as $campaign)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900">
                                        {{ $campaign->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $statusColors = [
                                                'draft' => 'bg-gray-100 text-gray-800',
                                                'scheduled' => 'bg-yellow-100 text-yellow-800',
                                                'sent' => 'bg-green-100 text-green-800',
                                            ];
                                            $badgeClass = $statusColors[$campaign->status] ?? 'bg-gray-100 text-gray-800';
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                            {{ ucfirst($campaign->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900">
                                        {{ $campaign->total_recipients }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm text-gray-900">
                                        {{ $campaign->sent_count }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-500">
                                        {{ $campaign->created_at?->format('Y-m-d H:i') }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <a href="{{ route('sms-campaigns.show', $campaign) }}"
                                           class="inline-flex items-center px-3 py-1.5 border border-gray-300 rounded-md
                                                  text-xs font-medium text-gray-700 hover:bg-gray-50">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 border-t border-gray-200">
                        {{ $campaigns->links() }}
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

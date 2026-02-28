{{-- resources/views/sms_campaigns/show.blade.php --}}

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            SMS Campaign: {{ $campaign->name }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Summary card --}}
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-6 py-5 border-b border-gray-200 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900">
                            Campaign details
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Overview of this SMS campaign.
                        </p>
                    </div>

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
                </div>

                <div class="px-6 py-6 space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Campaign Name</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $campaign->name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $campaign->created_at?->format('Y-m-d H:i') }}
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Total Recipients</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $campaign->total_recipients }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sent</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $campaign->sent_count }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Scheduled At</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ $campaign->scheduled_at?->format('Y-m-d H:i') ?? '—' }}
                            </dd>
                        </div>
                    </div>

                    <div class="pt-4 border-t border-gray-100">
                        <dt class="text-sm font-medium text-gray-500 mb-1">Message</dt>
                        <dd class="text-sm text-gray-900 whitespace-pre-line">
                            {{ $campaign->message }}
                        </dd>
                    </div>
                </div>

                <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
                    <a href="{{ route('sms-campaigns.index') }}"
                       class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                              text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        Back to list
                    </a>

                    @if($campaign->status === 'draft')
                        <form action="{{ route('sms-campaigns.send', $campaign) }}" method="POST">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md
                                       font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700
                                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                            >
                                Send Campaign
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Delivery log --}}
            @if(isset($messages) && $messages->isNotEmpty())
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-md font-medium text-gray-900">
                            Delivery Log
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            Recent messages sent for this campaign.
                        </p>
                    </div>

                    <div class="px-6 py-4">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Customer
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Phone
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sent At
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($messages as $msg)
                                        <tr>
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                {{ $msg->customer?->name ?? '—' }}
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-900">
                                                {{ $msg->phone }}
                                            </td>
                                            <td class="px-4 py-2 text-sm">
                                                @php
                                                    $badgeClass = $msg->status === 'sent'
                                                        ? 'bg-green-100 text-green-800'
                                                        : 'bg-red-100 text-red-800';
                                                @endphp
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                                    {{ ucfirst($msg->status) }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-2 text-sm text-gray-500">
                                                {{ $msg->sent_at?->format('Y-m-d H:i') ?? '—' }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white shadow sm:rounded-lg">
                    <div class="px-6 py-4">
                        <h3 class="text-md font-medium text-gray-900">
                            Delivery Log
                        </h3>
                        <p class="mt-1 text-sm text-gray-500">
                            No messages have been logged yet for this campaign.
                        </p>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>

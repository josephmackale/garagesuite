<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 leading-tight">Activity</h2>
    </x-slot>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="overflow-auto max-h-[70vh] text-xs divide-y">
                @forelse($logs as $log)
                    <div class="py-3 px-4">
                        <div class="font-mono text-gray-800">
                            [{{ $log->created_at->format('Y-m-d H:i:s') }}]
                            {{ $log->action }}
                        </div>

                        <div class="text-gray-500 text-sm">
                            Actor: {{ $log->actor->name ?? 'System' }}

                            @if($log->target_type && $log->target_id)
                                |
                                Target: {{ class_basename($log->target_type) }} #{{ $log->target_id }}
                            @endif
                        </div>

                        @if(!empty($log->meta))
                            <div class="text-xs text-gray-400 mt-1">
                                {{ json_encode($log->meta) }}
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="p-6 text-gray-500 text-sm">
                        No activity logs yet. Actions like logins, payments, job creation will appear here.
                    </div>
                @endforelse
            </div>

            <div class="border-t bg-slate-50 px-4 py-3">
                {{ $logs->links() }}
            </div>
        </div>

</x-app-layout>

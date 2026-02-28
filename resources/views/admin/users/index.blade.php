<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Users</h2>
                <p class="text-sm text-gray-500 mt-1">All users across all garages.</p>
            </div>
        </div>
    </x-slot>

    <div class="max-w-6xl space-y-4">
        <form method="GET" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4 flex gap-2">
            <input name="search" value="{{ request('search') }}"
                   class="w-full rounded-xl border-slate-200 text-sm"
                   placeholder="Search name, email, phone, garage..." />
            <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Search
            </button>
        </form>

        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="text-left p-3">User</th>
                        <th class="text-left p-3">Garage</th>
                        <th class="text-left p-3">Role</th>
                        <th class="text-left p-3">Created</th>
                        <th class="text-right p-3">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($users as $u)
                        <tr>
                            <td class="p-3">
                                <div class="font-semibold text-slate-900">{{ $u->name }}</div>
                                <div class="text-slate-500">{{ $u->email }}</div>
                            </td>
                            <td class="p-3">
                                <div class="font-semibold">{{ $u->garage?->name ?? '—' }}</div>
                                <div class="text-slate-500 font-mono text-xs">{{ $u->garage?->garage_code ?? '' }}</div>
                            </td>
                            <td class="p-3">{{ $u->role ?? '—' }}</td>
                            <td class="p-3 text-slate-500">{{ optional($u->created_at)->format('d M Y') }}</td>
                            <td class="p-3 text-right">
                                <a href="{{ route('admin.users.show', $u) }}"
                                   class="text-indigo-600 font-semibold hover:underline">
                                    Open →
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $users->links() }}</div>
    </div>
</x-app-layout>

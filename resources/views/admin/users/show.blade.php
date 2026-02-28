{{-- resources/views/admin/users/show.blade.php --}}
<x-admin-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">{{ $user->name }}</h2>
                <p class="text-sm text-gray-500 mt-1">{{ $user->email }}</p>
            </div>

            <a href="{{ route('admin.users.index') }}" class="text-xs font-semibold text-slate-700 hover:text-slate-900">
                ← Back to users
            </a>
        </div>
    </x-slot>

    <div class="max-w-3xl space-y-4">

        {{-- Flash messages --}}
        @if(session('success'))
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 text-emerald-800 px-4 py-3 text-sm">
                {{ session('success') }}
            </div>
        @endif

        @if($errors->any())
            <div class="rounded-xl border border-rose-200 bg-rose-50 text-rose-800 px-4 py-3 text-sm space-y-1">
                @foreach($errors->all() as $e)
                    <div>{{ $e }}</div>
                @endforeach
            </div>
        @endif

        {{-- User details --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 text-sm space-y-2">
            <div><span class="text-slate-500">Name:</span> <span class="font-semibold">{{ $user->name }}</span></div>
            <div><span class="text-slate-500">Email:</span> <span class="font-semibold">{{ $user->email }}</span></div>
            <div><span class="text-slate-500">Phone:</span> <span class="font-semibold">{{ $user->phone ?? '—' }}</span></div>
            <div><span class="text-slate-500">Role:</span> <span class="font-semibold">{{ $user->role ?? '—' }}</span></div>
            <div>
                <span class="text-slate-500">Garage:</span>
                <span class="font-semibold">{{ $user->garage?->name ?? '—' }}</span>
                @if($user->garage)
                    <span class="text-xs text-slate-500 font-mono ml-2">{{ $user->garage->garage_code }}</span>
                @endif
            </div>

            <div>
                <span class="text-slate-500">Status:</span>
                @php
                    $isSuspended = (($user->status ?? 'active') === 'suspended') || !is_null($user->suspended_at ?? null);
                @endphp
                @if($isSuspended)
                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                        Suspended
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        Active
                    </span>
                @endif
            </div>
        </div>

        {{-- Admin actions --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
            <div class="text-sm font-semibold text-slate-800">Admin Actions</div>

            {{-- Suspend / Activate --}}
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm">
                    <div class="font-semibold text-slate-800">Access</div>
                    <div class="text-slate-500">Suspend prevents login / use of the system.</div>
                </div>

                <div class="flex items-center gap-2">
                    @if($isSuspended)
                        <form method="POST" action="{{ route('admin.users.activate', $user) }}">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700">
                                Activate
                            </button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.users.suspend', $user) }}"
                              onsubmit="return confirm('Suspend this user? They will be logged out and blocked.');">
                            @csrf
                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 rounded-xl bg-rose-600 text-white text-sm font-semibold hover:bg-rose-700">
                                Suspend
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            {{-- Impersonate --}}
            @if(auth()->user()->role === 'super_admin' && $user->garage_id && $user->role === 'garage_admin' && \Illuminate\Support\Facades\Route::has('admin.impersonation.start'))
                <div class="flex items-center justify-between gap-4">
                    <div class="text-sm">
                        <div class="font-semibold text-slate-800">Impersonate</div>
                        <div class="text-slate-500">Login as this garage admin.</div>
                    </div>

                    <form method="POST" action="{{ route('admin.impersonation.start', $user->garage_id) }}">
                        @csrf
                        <button
                            type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl
                                   !bg-amber-600 !text-white text-sm font-semibold
                                   hover:!bg-amber-700">
                            Login as Garage
                        </button>
                    </form>
                </div>

                <div class="h-px bg-slate-100"></div>
            @endif

            <div class="h-px bg-slate-100"></div>

            {{-- Role --}}
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm">
                    <div class="font-semibold text-slate-800">Role</div>
                    <div class="text-slate-500">Change role for this user.</div>
                </div>

                <form method="POST" action="{{ route('admin.users.role', $user) }}" class="flex items-center gap-2">
                    @csrf
                    <select name="role"
                            class="rounded-xl border-slate-200 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="staff" @selected(($user->role ?? '') === 'staff')>staff</option>
                        <option value="garage_admin" @selected(($user->role ?? '') === 'garage_admin')>garage_admin</option>
                        <option value="garage_owner" @selected(($user->role ?? '') === 'garage_owner')>garage_owner</option>
                    </select>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
                        Update
                    </button>
                </form>
            </div>

            <div class="h-px bg-slate-100"></div>

            {{-- Move garage --}}
            <div class="flex items-center justify-between gap-4">
                <div class="text-sm">
                    <div class="font-semibold text-slate-800">Garage</div>
                    <div class="text-slate-500">Move the user to another garage (staff only).</div>
                </div>

                <form method="POST" action="{{ route('admin.users.garage', $user) }}" class="flex items-center gap-2">
                    @csrf

                    <select name="garage_id"
                            class="rounded-xl border-slate-200 text-sm
                                   focus:ring-indigo-500 focus:border-indigo-500
                                   {{ $user->role !== 'staff' ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $user->role !== 'staff' ? 'disabled' : '' }}>
                        @foreach($garages as $g)
                            <option value="{{ $g->id }}" @selected($user->garage_id === $g->id)>
                                {{ $g->name }}
                            </option>
                        @endforeach
                    </select>

                    <button type="submit"
                            class="inline-flex items-center px-4 py-2 rounded-xl bg-slate-900 text-white text-sm font-semibold hover:bg-black
                                   {{ $user->role !== 'staff' ? 'opacity-50 cursor-not-allowed' : '' }}"
                            {{ $user->role !== 'staff' ? 'disabled' : '' }}>
                        Move
                    </button>
                </form>

            </div>
        </div>

    </div>
</x-admin-layout>

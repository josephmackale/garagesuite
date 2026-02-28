<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-900 leading-tight">Admin Settings</h2>
    </x-slot>

    @php
        $sysDriver = $systemSmsDriver ?? 'fake';

        $sys = array_merge([
            'enabled' => false,
            'provider' => 'provider_x',
            'sender_id' => null,
            'base_url' => null,
            'default_country_code' => '+254',
        ], $systemSmsConfig ?? []);

        $enabled = (bool) ($sys['enabled'] ?? false);
        $apiKeyPresent = !empty($sys['api_key_present']) || !empty($sys['api_key']);
    @endphp

    <div class="max-w-5xl space-y-6">

        {{-- Flash messages --}}
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
                <div class="font-semibold mb-1">Please fix the following:</div>
                <ul class="list-disc pl-5 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- System summary --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">System</p>
                    <p class="mt-1 text-xs text-slate-500">These settings affect the whole platform.</p>
                </div>
                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold border border-slate-200 bg-slate-50 text-slate-700">
                    ENV: {{ $appEnv ?? '-' }}
                </span>
            </div>

            <div class="mt-4 grid gap-3 sm:grid-cols-3 text-sm">
                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500">SMS Driver</div>
                    <div class="font-semibold">{{ $sysDriver }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500">Garages</div>
                    <div class="font-semibold">{{ $garagesCount ?? '-' }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 p-4">
                    <div class="text-slate-500">Users</div>
                    <div class="font-semibold">{{ $usersCount ?? '-' }}</div>
                </div>
            </div>
        </div>

        {{-- Global SMS --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <p class="text-sm font-semibold text-slate-900">Global SMS Provider</p>
                    <p class="mt-1 text-xs text-slate-500">
                        Used when a garage has no provider or is set to use global SMS.
                    </p>
                </div>

                <span class="shrink-0 inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold
                    {{ $enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
                    {{ $enabled ? 'Enabled' : 'Disabled' }}
                </span>
            </div>

            <form method="POST" action="{{ route('admin.settings.sms.update') }}" class="mt-6 space-y-4">
                @csrf

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">Provider</label>
                        <select name="provider" class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="provider_x" {{ old('provider', $sys['provider'] ?? 'provider_x') === 'provider_x' ? 'selected' : '' }}>
                                Provider X
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Sender ID</label>
                        <input type="text" name="sender_id"
                               value="{{ old('sender_id', $sys['sender_id'] ?? '') }}"
                               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="GARAGESUITE" />
                    </div>
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">API Base URL</label>
                        <input type="text" name="base_url"
                               value="{{ old('base_url', $sys['base_url'] ?? '') }}"
                               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="https://api.provider.com" />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-700">Default Country Code</label>
                        <input type="text" name="default_country_code"
                               value="{{ old('default_country_code', $sys['default_country_code'] ?? '+254') }}"
                               class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="+254" />
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700">API Key / Token</label>
                    <input type="password" name="api_key" value=""
                           class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                           placeholder="{{ $apiKeyPresent ? '•••••••• (stored)' : 'Paste API key' }}"
                           autocomplete="new-password" />
                    <p class="mt-1 text-xs text-slate-500">Leave blank to keep existing key.</p>
                </div>

                <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
                    <div>
                        <div class="text-sm font-medium text-slate-900">Enable SMS system-wide</div>
                        <div class="text-xs text-slate-500">If disabled, all sends are blocked.</div>
                    </div>
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1"
                               class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                               {{ old('enabled', $enabled ? 1 : 0) ? 'checked' : '' }} />
                    </label>
                </div>

                <div class="flex justify-end pt-2">
                    <button type="submit"
                            class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-700">
                        Save Global SMS Settings
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-app-layout>

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
    $apiKeyPresent = !empty($sys['api_key']) || !empty($sys['api_key_present']);
@endphp

<div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">

    {{-- Header --}}
    <div class="flex items-start justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-slate-900">SMS Provider</h3>
            <p class="mt-1 text-sm text-slate-500">
                System-wide SMS gateway used by all garages without their own provider.
            </p>
        </div>

        {{-- Status --}}
        <span class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold
            {{ $enabled ? 'bg-emerald-50 text-emerald-700 border border-emerald-200'
                        : 'bg-slate-50 text-slate-600 border border-slate-200' }}">
            {{ $enabled ? 'Enabled' : 'Disabled' }}
        </span>
    </div>

    {{-- Form --}}
    <form method="POST" action="{{ route('admin.settings.sms.update') }}" class="mt-6 space-y-5">
        @csrf

        {{-- Provider --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">Provider</label>
            <select name="provider"
                    class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
                <option value="provider_x"
                    {{ old('provider', $sys['provider']) === 'provider_x' ? 'selected' : '' }}>
                    Provider X (Current)
                </option>
            </select>
            <p class="mt-1 text-xs text-slate-500">
                Adapter-based providers will be added later.
            </p>
        </div>

        {{-- Sender ID --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">Sender ID</label>
            <input type="text"
                   name="sender_id"
                   value="{{ old('sender_id', $sys['sender_id']) }}"
                   placeholder="GARAGESUITE"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Base URL --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">API Base URL</label>
            <input type="text"
                   name="base_url"
                   value="{{ old('base_url', $sys['base_url']) }}"
                   placeholder="https://api.provider.com"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- Default Country Code --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">Default Country Code</label>
            <input type="text"
                   name="default_country_code"
                   value="{{ old('default_country_code', $sys['default_country_code']) }}"
                   placeholder="+254"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
        </div>

        {{-- API Key --}}
        <div>
            <label class="block text-sm font-medium text-slate-700">API Key / Token</label>
            <input type="password"
                   name="api_key"
                   value=""
                   placeholder="{{ $apiKeyPresent ? '•••••••• (stored)' : 'Paste API key' }}"
                   autocomplete="new-password"
                   class="mt-1 w-full rounded-xl border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
            <p class="mt-1 text-xs text-slate-500">
                Leave blank to keep the existing key.
            </p>
        </div>

        {{-- Enable toggle --}}
        <div class="flex items-center justify-between rounded-xl border border-slate-200 p-4">
            <div>
                <p class="text-sm font-medium text-slate-900">Enable SMS system-wide</p>
                <p class="text-xs text-slate-500">
                    If disabled, all SMS sends are blocked.
                </p>
            </div>

            <label class="inline-flex items-center">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox"
                       name="enabled"
                       value="1"
                       class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                       {{ old('enabled', $enabled ? 1 : 0) ? 'checked' : '' }}>
            </label>
        </div>

        {{-- Save --}}
        <div class="flex justify-end pt-2">
            <button type="submit"
                    class="inline-flex items-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                Save SMS Settings
            </button>
        </div>

    </form>
</div>

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-semibold text-xl text-gray-900 leading-tight">Garage Profile</h2>
                <p class="mt-1 text-sm text-gray-500">Update your garage business details and legal documents.</p>
            </div>

            <a href="{{ route('settings.home') }}"
               class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-300 text-xs font-semibold text-gray-700 hover:bg-gray-50">
                Back to Settings
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Business/Profile --}}
            <div class="bg-white shadow-sm rounded-2xl border border-gray-100 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Business Information</h3>
                        <p class="mt-1 text-sm text-gray-500">Basic identity details for your garage.</p>
                    </div>
                </div>

                <div class="mt-4 text-sm text-gray-600">
                    {{-- Replace this block with your real form --}}
                    <div class="rounded-lg border border-dashed border-gray-300 p-4">
                        Profile form goes here.
                    </div>
                </div>
            </div>

            @php
            $labels = [
                'certificate_of_incorporation' => 'Certificate of Incorporation',
                'company_registration_certificate' => 'Company Registration Certificate',
                'kra_pin_certificate' => 'KRA PIN Certificate',
                'tax_compliance_certificate' => 'Tax Compliance Certificate (Optional)',
            ];
            @endphp

            <div class="space-y-4">
            @foreach($docTypes as $type)
                @php
                $doc = $documents[$type] ?? null;
                $inputId = 'legal_'.$type;
                @endphp

                <div class="rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-3">
                    <div class="text-sm font-semibold text-gray-900">
                        {{ $labels[$type] ?? $type }}
                    </div>

                    @if($doc)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 border border-emerald-100">
                        ✓ Uploaded
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 rounded-full bg-gray-50 px-2.5 py-1 text-xs font-semibold text-gray-500 border border-gray-200">
                        Missing
                        </span>
                    @endif
                    </div>

                    @if($doc)
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $doc->original_name ?? 'Document' }}
                        @if($doc->uploaded_at)
                        · {{ \Carbon\Carbon::parse($doc->uploaded_at)->format('Y-m-d H:i') }}
                        @endif
                    </div>
                    @endif

                    @if($doc)
                    <form method="POST"
                            action="{{ route('settings.profile.legal.delete', $type) }}"
                            onsubmit="return confirm('Remove this document?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50">
                        Remove
                        </button>
                    </form>
                    @endif
                </div>

                {{-- Upload/Replace --}}
                <form class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"
                        method="POST"
                        enctype="multipart/form-data"
                        action="{{ route('settings.profile.legal.upload', $type) }}">
                    @csrf

                    <div class="flex-1">
                    {{-- Hidden native input --}}
                    <input id="{{ $inputId }}"
                            type="file"
                            name="file"
                            accept=".pdf,.jpg,.jpeg,.png"
                            class="hidden"
                            onchange="document.getElementById('{{ $inputId }}_name').textContent = this.files?.[0]?.name || 'No file selected';" />

                    {{-- Pretty picker row --}}
                    <div class="flex items-center gap-3">
                        <label for="{{ $inputId }}"
                            class="inline-flex cursor-pointer items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-100">
                        Choose file
                        </label>

                        <div id="{{ $inputId }}_name"
                            class="truncate text-sm text-gray-500">
                        No file selected
                        </div>
                    </div>

                    <div class="mt-1 text-xs text-gray-400">
                        PDF, JPG, PNG (max 10MB)
                    </div>
                    </div>

                    <div class="flex items-center gap-2">
                    <button type="submit"
                            class="inline-flex items-center justify-center rounded-lg bg-indigo-600 px-5 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        {{ $doc ? 'Replace' : 'Upload' }}
                    </button>
                    </div>
                </form>
                </div>
            @endforeach
            </div>

        </div>
    </div>
</x-app-layout>
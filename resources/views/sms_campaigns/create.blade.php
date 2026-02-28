<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Create SMS Campaign') }}
        </h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        New SMS Campaign
                    </h3>
                    <p class="mt-1 text-sm text-gray-500">
                        Define your campaign details and message.
                    </p>
                </div>

                <div class="px-6 py-6">
                    <form action="{{ route('sms-campaigns.store') }}" method="POST" class="space-y-6">
                        @csrf

                        {{-- Campaign name --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Campaign Name
                            </label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                                       @error('name') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                required
                            >
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Message --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Message
                            </label>
                            <textarea
                                name="message"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                                       @error('message') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                                required
                            >{{ old('message') }}</textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Keep it under 160 characters for a single SMS.
                            </p>
                            @error('message')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Status --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Status
                            </label>
                            <select
                                name="status"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                                       @error('status') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >
                                <option value="draft" {{ old('status', 'draft') === 'draft' ? 'selected' : '' }}>
                                    Draft
                                </option>
                                <option value="scheduled" {{ old('status') === 'scheduled' ? 'selected' : '' }}>
                                    Scheduled
                                </option>
                            </select>
                            @error('status')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Schedule time --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700">
                                Schedule Time (optional)
                            </label>
                            <input
                                type="datetime-local"
                                name="scheduled_at"
                                value="{{ old('scheduled_at') }}"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm
                                       focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm
                                       @error('scheduled_at') border-red-300 focus:border-red-500 focus:ring-red-500 @enderror"
                            >
                            @error('scheduled_at')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between">
                            <a href="{{ route('sms-campaigns.index') }}"
                               class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md
                                      text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>

                            <button type="submit"
                                    class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent
                                           rounded-md font-semibold text-xs text-white uppercase tracking-widest
                                           hover:bg-indigo-700 focus:outline-none focus:ring-2
                                           focus:ring-indigo-500 focus:ring-offset-2">
                                Save Campaign
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>

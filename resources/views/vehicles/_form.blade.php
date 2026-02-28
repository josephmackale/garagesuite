@csrf

<div class="space-y-4">

    {{-- Customer --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            Customer <span class="text-red-500">*</span>
        </label>

        @if(!empty($lockedCustomer))
            {{-- Locked / readonly (customer-context flow) --}}
            <input type="hidden" name="customer_id" value="{{ $lockedCustomer->id }}">

            <input type="text"
                   class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900"
                   value="{{ $lockedCustomer->name }}{{ $lockedCustomer->phone ? ' — '.$lockedCustomer->phone : '' }}"
                   readonly>
        @else
            {{-- Global flow --}}
            <select name="customer_id"
                    class="w-full rounded-lg border-gray-300"
                    required>
                <option value="">Select customer...</option>

                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}"
                        @selected(old('customer_id', $selectedCustomerId ?? $vehicle?->customer_id) == $customer->id)>
                        {{ $customer->name }}{{ $customer->phone ? ' — '.$customer->phone : '' }}
                    </option>
                @endforeach
            </select>
        @endif

        @error('customer_id')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>


    {{-- Make / Model --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Make *</label>
            <input type="text" name="make"
                   value="{{ old('make', $vehicle?->make ?? '') }}"
                   class="w-full rounded-lg border-gray-300">

            @error('make')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Model *</label>
            <input type="text" name="model"
                   value="{{ old('model', $vehicle?->model ?? '') }}"
                   class="w-full rounded-lg border-gray-300">

            @error('model')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>


    {{-- Year / Reg / Color --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
            <input type="text" name="year"
                   value="{{ old('year', $vehicle?->year ?? '') }}"
                   class="w-full rounded-lg border-gray-300">

            @error('year')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Reg. Number *</label>
            <input type="text" name="registration_number"
                   value="{{ old('registration_number', $vehicle?->registration_number ?? '') }}"
                   class="w-full rounded-lg border-gray-300">

            @error('registration_number')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
            <input type="text" name="color"
                   value="{{ old('color', $vehicle?->color ?? '') }}"
                   class="w-full rounded-lg border-gray-300">

            @error('color')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>


    {{-- VIN --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">VIN / Chassis</label>
        <input type="text" name="vin"
               value="{{ old('vin', $vehicle?->vin ?? '') }}"
               class="w-full rounded-lg border-gray-300">

        @error('vin')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>


    {{-- Notes --}}
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>

        <textarea name="notes" rows="3"
                  class="w-full rounded-lg border-gray-300">{{ old('notes', $vehicle?->notes ?? '') }}</textarea>

        @error('notes')
            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
        @enderror
    </div>

</div>


{{-- Footer --}}
<div class="mt-6 flex items-center gap-4">

    <button type="submit"
            class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
        {{ $buttonText ?? 'Save' }}
    </button>


    {{-- Cancel --}}
    @if(!empty($isModal))
        {{-- Close modal --}}
        <button type="button"
                @click="openVehicleModal = false"
                class="text-sm text-gray-600 hover:underline">
            Cancel
        </button>
    @else
        {{-- Normal page --}}
        <a href="{{ route('vehicles.index') }}"
           class="text-sm text-gray-600 hover:underline">
            Cancel
        </a>
    @endif

</div>

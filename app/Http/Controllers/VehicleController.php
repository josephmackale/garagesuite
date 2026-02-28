<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Vehicle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VehicleController extends Controller
{
    /**
     * Display a listing of the vehicles (with search).
     */
    public function index(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $search = $request->input('q');

        $query = Vehicle::where('garage_id', $garageId)
            ->with('customer');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('registration_number', 'like', "%{$search}%")
                    ->orWhere('make', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($cq) use ($search) {
                        $cq->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
            });
        }

        $vehicles = $query
            ->orderBy('registration_number')
            ->paginate(15)
            ->withQueryString();

        return view('vehicles.index', [
            'vehicles' => $vehicles,
            'search'   => $search,
        ]);
    }

    /**
     * Show the form for creating a new vehicle (GLOBAL flow).
     * If customer_id is provided, we lock the customer (auto-fill + readonly).
     */
    public function create(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $customers = Customer::where('garage_id', $garageId)
            ->orderBy('name')
            ->get();

        // Optional: preselect/lock customer if coming from customer profile (via ?customer_id=)
        $selectedCustomerId = $request->input('customer_id');

        $lockedCustomer = null;
        if ($selectedCustomerId) {
            $lockedCustomer = Customer::where('garage_id', $garageId)
                ->where('id', $selectedCustomerId)
                ->firstOrFail();
        }

        return view('vehicles.create', compact('customers', 'selectedCustomerId', 'lockedCustomer'));
    }

    /**
     * ✅ Customer-context create (preferred flow)
     * /customers/{customer}/vehicles/create
     */
    public function createForCustomer(Customer $customer)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($customer->garage_id === $garageId, 403);

        // Same view, but customer is locked/readonly
        return view('vehicles.create', [
            'customers'          => collect(), // not needed in locked mode, but view expects it
            'selectedCustomerId' => $customer->id,
            'lockedCustomer'     => $customer,
        ]);
    }

    /**
     * Store a newly created vehicle in storage (GLOBAL flow).
     */
    public function store(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        // ✅ Normalize before validation so uniqueness is reliable
        $request->merge([
            'registration_number' => $this->normalizeReg($request->input('registration_number')),
        ]);

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('garage_id', $garageId),
            ],
            'make'  => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],

            'year' => ['nullable', 'string', 'max:10'],

            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                }),
            ],

            'vin'     => ['nullable', 'string', 'max:100'],
            'color'   => ['nullable', 'string', 'max:50'],
            'notes'   => ['nullable', 'string'],
        ]);

        $validated['garage_id'] = $garageId;

        $vehicle = Vehicle::create($validated);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle added successfully.');
    }

    /**
     * ✅ Store vehicle in customer-context flow
     * /customers/{customer}/vehicles
     */
    public function storeForCustomer(Request $request, Customer $customer)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($customer->garage_id === $garageId, 403);

        // ✅ Normalize before validation so uniqueness is reliable
        $request->merge([
            'registration_number' => $this->normalizeReg($request->input('registration_number')),
        ]);

        $validated = $request->validate([
            // customer_id is forced from route context (not accepted from request)
            'make'  => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],

            'year' => ['nullable', 'string', 'max:10'],

            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                }),
            ],

            'vin'     => ['nullable', 'string', 'max:100'],
            'color'   => ['nullable', 'string', 'max:50'],
            'notes'   => ['nullable', 'string'],
        ]);

        $validated['garage_id'] = $garageId;
        $validated['customer_id'] = $customer->id;

        $vehicle = Vehicle::create($validated);

        // UX: go back to customer profile (vehicles tab)
        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Vehicle added successfully.');
    }

    /**
     * Show the form for editing the specified vehicle.
     */
    public function edit(Vehicle $vehicle)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($vehicle->garage_id === $garageId, 403);

        $customers = Customer::where('garage_id', $garageId)
            ->orderBy('name')
            ->get();

        return view('vehicles.edit', compact('vehicle', 'customers'));
    }

    /**
     * Update the specified vehicle in storage.
     */
    public function update(Request $request, Vehicle $vehicle)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($vehicle->garage_id === $garageId, 403);

        // ✅ Normalize before validation so uniqueness is reliable
        $request->merge([
            'registration_number' => $this->normalizeReg($request->input('registration_number')),
        ]);

        $validated = $request->validate([
            'customer_id' => [
                'required',
                Rule::exists('customers', 'id')->where('garage_id', $garageId),
            ],
            'make'  => ['required', 'string', 'max:255'],
            'model' => ['required', 'string', 'max:255'],

            'year' => ['nullable', 'string', 'max:10'],

            'registration_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('vehicles')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                })->ignore($vehicle->id),
            ],

            'vin'     => ['nullable', 'string', 'max:100'],
            'color'   => ['nullable', 'string', 'max:50'],
            'notes'   => ['nullable', 'string'],
        ]);

        $vehicle->update($validated);

        return redirect()
            ->route('vehicles.show', $vehicle)
            ->with('success', 'Vehicle updated successfully.');
    }

    /**
     * Remove the specified vehicle from storage.
     * Block delete if there are jobs.
     */
    public function destroy(Vehicle $vehicle)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($vehicle->garage_id === $garageId, 403);

        if ($vehicle->jobs()->exists()) {
            return redirect()
                ->route('vehicles.show', $vehicle)
                ->with('error', 'You cannot delete this vehicle because it has jobs linked.');
        }

        $vehicle->delete();

        return redirect()
            ->route('vehicles.index')
            ->with('success', 'Vehicle deleted successfully.');
    }

    /**
     * Display the specified vehicle (profile with history).
     */
    public function show(Vehicle $vehicle)
    {
        $garageId = Auth::user()->garage_id;

        abort_unless($vehicle->garage_id === $garageId, 403);

        $vehicle->load([
            'customer',
            'jobs' => function ($q) {
                $q->orderByDesc('job_date')->orderByDesc('id');
            },
        ]);

        return view('vehicles.show', compact('vehicle'));
    }

    /**
     * Normalize registration number (KE style) for uniqueness and search.
     */
    private function normalizeReg(?string $reg): ?string
    {
        if (!$reg) return null;

        $reg = strtoupper($reg);
        $reg = preg_replace('/\s+/', '', $reg); // "KCA 123A" => "KCA123A"

        return $reg;
    }
}

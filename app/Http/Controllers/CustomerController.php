<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $query = Customer::where('garage_id', $garageId);

        $search = $request->input('q');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $customers = $query
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search'    => $search,
        ]);
    }

    public function create()
    {
        return view('customers.create');
    }

    public function store(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => [
                'required',
                'string',
                'max:25',
                Rule::unique('customers')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                }),
            ],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes'   => ['nullable', 'string'],
        ]);

        $validated['garage_id'] = $garageId;

        $customer = Customer::create($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created successfully.');
    }

    public function show(Request $request, $id)
    {
        $garageId = Auth::user()->garage_id;

        $customer = Customer::where('garage_id', $garageId)
            ->with([
                'vehicles' => function ($q) {
                    $q->orderBy('registration_number');
                },
                'jobs' => function ($q) {
                    $q->latest()->limit(10);
                },
            ])
            ->findOrFail($id);

        /**
         * ✅ REAL documents: documents are polymorphic (documentable_type/id)
         * In your current setup, docs belong to Invoice and Job.
         * NEVER query documents.customer_id or documents.vehicle_id (those columns don't exist).
         */
        $jobIds = Job::query()
            ->where('garage_id', $garageId)
            ->where('customer_id', $customer->id)
            ->pluck('id');

        $invoiceIds = Invoice::query()
            ->where('garage_id', $garageId)
            ->where('customer_id', $customer->id)
            ->pluck('id');

        $documentsQuery = Document::query()
            ->where('garage_id', $garageId);

        if ($jobIds->isEmpty() && $invoiceIds->isEmpty()) {
            $documents = collect();
        } else {
            $documents = $documentsQuery
                ->where(function ($q) use ($jobIds, $invoiceIds) {

                    // Invoice-attached docs
                    if ($invoiceIds->isNotEmpty()) {
                        $q->orWhere(function ($qq) use ($invoiceIds) {
                            $qq->where('documentable_type', Invoice::class)
                               ->whereIn('documentable_id', $invoiceIds);
                        });
                    }

                    // Job-attached docs
                    if ($jobIds->isNotEmpty()) {
                        $q->orWhere(function ($qq) use ($jobIds) {
                            $qq->where('documentable_type', Job::class)
                               ->whereIn('documentable_id', $jobIds);
                        });
                    }
                })
                ->orderByDesc('updated_at')
                ->get();
        }

        // ✅ Compatibility aliases (so your customers/show.blade.php hub works without edits)
        $documents->each(function ($d) {
            $dt = strtolower((string) ($d->document_type ?? ''));

            // Map document_type -> hub type keys your blade expects
            // invoices: invoice | receipts: receipt | job_cards: job_card
            $type =
                str_contains($dt, 'invoice') ? 'invoice' :
                (str_contains($dt, 'receipt') ? 'receipt' :
                (str_contains($dt, 'job_card') || str_contains($dt, 'jobcard') ? 'job_card' : $dt));

            $d->type      = $type;
            $d->doc_type  = $type;
            $d->title     = $d->name ?? $d->file_name ?? 'Document';
            $d->file_path = $d->path ?? null;
            $d->issued_at = $d->created_at ?? $d->updated_at;
            $d->amount    = null;
        });

        /**
         * ✅ Prevent blade 500:
         * Your logs show: Undefined variable $amount in customers/show.blade.php
         */
        $amount = 0;

        return view('customers.show', compact('customer', 'documents', 'amount'));
    }

    public function edit($id)
    {
        $garageId = Auth::user()->garage_id;

        $customer = Customer::where('garage_id', $garageId)->findOrFail($id);

        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, $id)
    {
        $garageId = Auth::user()->garage_id;

        $customer = Customer::where('garage_id', $garageId)->findOrFail($id);

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => [
                'required',
                'string',
                'max:25',
                Rule::unique('customers')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                })->ignore($customer->id),
            ],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes'   => ['nullable', 'string'],
        ]);

        $customer->update($validated);

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy($id)
    {
        $garageId = Auth::user()->garage_id;

        $customer = Customer::where('garage_id', $garageId)->findOrFail($id);

        // Block delete if there is related data
        if ($customer->vehicles()->exists() || $customer->jobs()->exists() || $customer->invoices()->exists()) {
            return redirect()
                ->route('customers.show', $customer)
                ->with('error', 'You cannot delete this customer because they have vehicles or jobs linked.');
        }

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer deleted successfully.');
    }

    /**
     * Quick-add customer (for use on Job form later).
     * Optional: can be used via AJAX.
     */
    public function quickStore(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        $validated = $request->validate([
            'name'    => ['required', 'string', 'max:255'],
            'phone'   => [
                'required',
                'string',
                'max:25',
                Rule::unique('customers')->where(function ($q) use ($garageId) {
                    return $q->where('garage_id', $garageId);
                }),
            ],
            'email'   => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['garage_id'] = $garageId;

        $customer = Customer::create($validated);

        if ($request->wantsJson()) {
            return response()->json($customer);
        }

        return redirect()
            ->route('customers.show', $customer)
            ->with('success', 'Customer created.');
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Models\Vehicle;
use App\Models\InventoryItem;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Document;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use App\Models\MediaItem;
use App\Models\MediaAttachment;
use App\Services\InsuranceGate;
use Illuminate\Support\Facades\Log;

class JobController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Index
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        // Check if user is authenticated
        if (auth()->check()) {
            $user = auth()->user();
            $garageId = $user->garage_id;

            // Log the authenticated user and garage_id for debugging
            \Log::info('Authenticated User:', ['user' => $user]);
            \Log::info('Garage ID:', ['garage_id' => $garageId]);
        } else {
            // Log when user is not authenticated
            \Log::warning('User is not authenticated');
            return redirect()->route('login');
        }

        // Build the query for jobs
        $query = Job::with(['vehicle', 'customer'])
                    ->where('garage_id', $garageId);

        // Log the initial query for jobs
        \Log::info('Initial Query:', ['query' => $query->toSql()]);

        // Handle filters
        $filters = [
            'q' => $request->input('q'),
            'status' => $request->input('status'),
        ];

        // Log the filters received from the request
        \Log::info('Filters:', ['filters' => $filters]);

        // If search query is provided, apply search filter
        if ($filters['q']) {
            $search = $filters['q'];
            \Log::info('Search query:', ['search' => $search]);

            $query->where(function ($q) use ($search) {
                $q->where('job_number', 'like', "%{$search}%")
                ->orWhereHas('vehicle', function ($q) use ($search) {
                    $q->where('registration_number', 'like', "%{$search}%");
                })
                ->orWhereHas('customer', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            });
        }

        // If status filter is provided, apply status filter
        if ($filters['status']) {
            \Log::info('Status filter:', ['status' => $filters['status']]);

            if ($filters['status'] === 'pending') {
                $query->whereIn('status', ['pending', 'draft']);
            } else {
                $query->where('status', $filters['status']);
            }
        }

        // Execute the query and paginate the results
        $jobs = $query->orderByDesc('job_date')
                    ->orderByDesc('id')
                    ->paginate(15)
                    ->withQueryString();

        // Log the paginated jobs
        \Log::info('Paginated Jobs:', ['jobs_count' => $jobs->count()]);

        // Get all job statuses for the garage
        $statuses = $this->statuses();

        // Log the statuses
        \Log::info('Statuses:', ['statuses' => $statuses]);

        // Get the summary of jobs by status
        $statusSummary = Job::where('garage_id', $garageId)
                            ->selectRaw('status, COUNT(*) as jobs_count, COALESCE(SUM(final_cost), 0) as total_amount')
                            ->groupBy('status')
                            ->get()
                            ->keyBy('status');

        // Log the status summary
        \Log::info('Status Summary:', ['status_summary' => $statusSummary]);

        // ✅ Merge drafts into pending for UI counts
        if (isset($statusSummary['draft'])) {
            $pendingCount  = (int) ($statusSummary['pending']->jobs_count ?? 0);
            $pendingAmount = (float) ($statusSummary['pending']->total_amount ?? 0);

            $statusSummary['pending'] = (object) [
                'jobs_count'   => $pendingCount + (int) $statusSummary['draft']->jobs_count,
                'total_amount' => $pendingAmount + (float) $statusSummary['draft']->total_amount,
            ];

            unset($statusSummary['draft']);
        }

        return view('jobs.index', [
            'jobs'          => $jobs,
            'filters'       => $filters,
            'statuses'      => $statuses,
            'statusSummary' => $statusSummary,
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Create (REDIRECT TO WIZARD)
    |--------------------------------------------------------------------------
    */
    public function create(Request $request)
    {
        // Preserve context so wizard can auto-fill later (Step 3)
        $query = $request->only(['customer_id', 'vehicle_id']);

        return redirect()->route('jobs.create.step1', $query);
    }

    /*
    |--------------------------------------------------------------------------
    | Build Create Payload (REUSED BY WIZARD STEP 3)
    |--------------------------------------------------------------------------
    | This is your old create() body, moved into a reusable method.
    | We keep it here so Step 3 can call it without duplication.
    */
    public function buildCreatePayload(Request $request): array
    {
        $garageId = Auth::user()->garage_id;

        // Context params (optional)
        $customerId        = $request->get('customer_id');  // from customer page (optional)
        $selectedVehicleId = $request->get('vehicle_id');   // from add-vehicle flow (preferred)

        // Vehicles list (only needed if you support "create job from jobs menu")
        $vehiclesQuery = Vehicle::with('customer')
            ->where('garage_id', $garageId)
            ->orderBy('registration_number');

        // If opened from a customer page, show only that customer's vehicles
        if (!empty($customerId)) {
            $vehiclesQuery->where('customer_id', $customerId);
        }

        $vehicles = $vehiclesQuery->get();

        /**
         * 🚗 Vehicle auto-fill rules (LOCK-IN friendly)
         *
         * 1) If vehicle_id is provided -> use it (source of truth)
         * 2) Else if customer_id is provided:
         *      - Pick first vehicle ONLY if there is exactly 1 vehicle.
         *      - Otherwise leave it null.
         * 3) Else -> null (generic job create without context)
         */
        if (empty($selectedVehicleId) && !empty($customerId)) {
            if ($vehicles->count() === 1) {
                $selectedVehicleId = $vehicles->first()->id;
            }
        }

        // Load the selected vehicle (must be garage-scoped)
        $selectedVehicle = null;
        if (!empty($selectedVehicleId)) {
            $selectedVehicle = Vehicle::with('customer')
                ->where('garage_id', $garageId)
                ->whereKey($selectedVehicleId)
                ->first();

            // If vehicle_id doesn't belong to this garage (or doesn't exist), null it out
            if (!$selectedVehicle) {
                $selectedVehicleId = null;
            }
        }

        /**
         * ✅ Derive customer context FROM vehicle (not from request)
         * - vehicle -> customer
         */
        if ($selectedVehicle && $selectedVehicle->customer_id) {
            $customerId = $selectedVehicle->customer_id;
        }

        // Default job object (create form)
        $job = new Job([
            'job_date'    => now()->toDateString(),
            'status'      => 'pending',
            'labour_cost' => 0,
        ]);

        // Inventory items for parts autocomplete/dropdowns
        $inventoryItems = InventoryItem::where('garage_id', $garageId)
            ->orderBy('name')
            ->get(['id', 'name', 'selling_price']);

        $inventoryForParts = $inventoryItems->map(fn ($i) => [
            'id'    => $i->id,
            'name'  => $i->name,
            'price' => $i->selling_price,
        ])->values()->toArray();

        // NOTE: With wizard, payer lists are NOT needed here anymore.
        // Payer selection happens in Step 2; Step 3 will show payer summary read-only.
        // If you still need them somewhere else, keep the orgLists call outside Step 3.

        return [
            'job'               => $job,
            'vehicles'          => $vehicles,              // keep if you have a fallback dropdown entry path
            'statuses'          => $this->statuses(),

            // ✅ context for read-only top panel
            'selectedVehicleId' => $selectedVehicleId,
            'selectedVehicle'   => $selectedVehicle,
            'customerId'        => $customerId,

            'inventoryForParts' => $inventoryForParts,
        ];
    }




    /*
    |--------------------------------------------------------------------------
    | Store (DROP-IN)
    |--------------------------------------------------------------------------
    | Notes (surgical + wizard-safe):
    | - Keeps your current AJAX-validation -> returns HTML (422) for modal re-render.
    | - Keeps transaction + insurance sync.
    | - Improves payer safety (no org_id stored for individual).
    | - Returns a slightly richer AJAX success payload (job_id + job_number + show_url)
    |   without breaking existing callers.
    */
    public function store(Request $request)
    {
        $garageId = Auth::user()->garage_id;

        // ✅ Catch validation to return HTML back into modal
        try {
            $data = $this->validateRequest($request);
            $data = $this->enforceCreateStatus($data);
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->expectsJson()) {

                // Rebuild the same create payload (same as create())
                $customerId        = $request->get('customer_id');
                $selectedVehicleId = $request->get('vehicle_id');

                $vehiclesQuery = Vehicle::with('customer')
                    ->where('garage_id', $garageId)
                    ->orderBy('registration_number');

                if ($customerId) {
                    $vehiclesQuery->where('customer_id', $customerId);
                }

                $vehicles = $vehiclesQuery->get();

                if (!$selectedVehicleId && $vehicles->count() > 0) {
                    $selectedVehicleId = $vehicles->first()->id;
                }

                $job = new Job([
                    'job_date'    => now()->toDateString(),
                    'status'      => 'pending',
                    'labour_cost' => 0,
                ]);

                $inventoryItems = InventoryItem::where('garage_id', $garageId)
                    ->orderBy('name')
                    ->get(['id', 'name', 'selling_price']);

                $inventoryForParts = $inventoryItems->map(fn ($i) => [
                    'id'    => $i->id,
                    'name'  => $i->name,
                    'price' => $i->selling_price,
                ])->values()->toArray();

                [$corporateOrgs, $insuranceOrgs] = $this->orgListsForGarage($garageId);

                $html = view('jobs.partials.create-form', [
                    'job'               => $job,
                    'vehicles'          => $vehicles,
                    'statuses'          => $this->statuses(),
                    'selectedVehicleId' => $selectedVehicleId,
                    'inventoryForParts' => $inventoryForParts,
                    'customerId'        => $customerId,

                    // ✅ payer context lists
                    'corporateOrgs'     => $corporateOrgs,
                    'insuranceOrgs'     => $insuranceOrgs,
                ])->withErrors($e->errors())->render();

                return response()->json(['html' => $html], 422);
            }

            throw $e;
        }

        $vehicle = Vehicle::where('garage_id', $garageId)
            ->findOrFail($data['vehicle_id']);

        // ✅ SAFETY: vehicle->customer_id is the source of truth (prevents mismatch)
        $customerId = $vehicle->customer_id ?: ($data['customer_id'] ?? null);
        if (!$customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer is required for this vehicle.',
            ]);
        }

        // ✅ Payer context safety (no accidental defaults)
        $payerType = (string) ($data['payer_type'] ?? 'individual');
        $orgId     = $data['organization_id'] ?? null;

        // Individual should not store organization_id
        if ($payerType === 'individual') {
            $orgId = null;
        }

        DB::beginTransaction();

        try {
            $job = Job::create([
                'garage_id'       => $garageId,
                'vehicle_id'      => $vehicle->id,
                'customer_id'     => $customerId,
                'created_by'      => Auth::id(),

                'job_number'      => $this->generateJobNumber($garageId),
                'job_date'        => $data['job_date'],
                'service_type'    => $data['service_type'] ?? null,
                'mileage'         => $data['mileage'] ?? null,

                'complaint'       => $data['complaint'] ?? null,
                'diagnosis'       => $data['diagnosis'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'work_done'       => $data['work_done'] ?? null,

                // hard rules
                'status'          => 'pending',
                'labour_cost'     => (float) ($data['labour_cost'] ?? 0),

                // ✅ payer context
                'payer_type'      => $payerType,
                'organization_id' => $orgId,
            ]);

            // Parts
            $this->syncJobItems($job, [], $data['part_items'] ?? []);

            // ✅ Insurance details (1:1)
            $this->syncInsuranceDetailsForJob($job->id, $payerType, $data['insurance'] ?? null);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // ✅ AJAX success (wizard/modal safe)
        if ($request->ajax() || $request->expectsJson()) {

            // decide where Step 2 lives (page route)
            $nextUrl = route('jobs.create.step2', ['job' => $job->id]);

            return response()->json([
                'ok'         => true,
                'job_id'     => $job->id,
                'job_number' => $job->job_number,
                'show_url'   => route('jobs.show', $job),

                // ✅ wizard page-flow support
                'payer_type' => $job->payer_type,
                'next_url'   => $nextUrl,
            ]);
        }


        // Normal success
        return redirect()
            ->route('jobs.show', $job)
            ->with('success', 'Job created successfully.');
    }


    /*
    |--------------------------------------------------------------------------
    | Show
    |--------------------------------------------------------------------------
    */
    public function show(Job $job)
    {
        $this->authorizeGarage($job);

        // ✅ Insurance jobs must use the insurance workflow UI
        if (($job->payer_type ?? null) === 'insurance') {
            return redirect()->route('jobs.insurance.show', $job);
        }

        // ✅ Normal jobs: load only what the normal show page needs
        $job->load([
            'vehicle.customer',
            'partItems',
            'invoice',
            'mediaAttachments.mediaItem',
        ]);

        $garageId = Auth::user()->garage_id;

        // Vault items (optional – keep if your show blade uses it)
        $vaultItems = MediaItem::where('garage_id', $garageId)
            ->latest()
            ->paginate(24);

        return view('jobs.show', [
            'job'        => $job,
            'vaultItems' => $vaultItems,
        ]);
    }



    /*
    |--------------------------------------------------------------------------
    | Job Card (HTML preview)
    |--------------------------------------------------------------------------
    */
    public function jobCard(Job $job)
    {
        $this->authorizeGarage($job);

        $job->load(['vehicle.customer', 'partItems', 'garage']);

        $labourTotal = (float) ($job->labour_cost ?? 0);
        $partsTotal  = (float) ($job->parts_cost ?? 0);
        $grandTotal  = (float) ($job->final_cost ?? ($labourTotal + $partsTotal));

        return view('jobs.job-card', [
            'job'         => $job,
            'labourTotal' => $labourTotal,
            'partsTotal'  => $partsTotal,
            'grandTotal'  => $grandTotal,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Download Job Card (PDF)
    |--------------------------------------------------------------------------
    */
    public function downloadJobCard(Job $job)
    {
        $this->authorizeGarage($job);

        $job->load(['vehicle.customer', 'garage']);

        $qrData = route('jobs.show', $job);

        $qrSvg = null;
        if (class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
            $qrSvg = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('svg')
                ->size(90)
                ->generate($qrData);
        }

        $logoBase64 = null;
        $logoPath = public_path('images/garagesuite-logo.png');
        if (file_exists($logoPath)) {
            $logoBase64 = base64_encode(file_get_contents($logoPath));
        }

        $pdf = Pdf::loadView('jobs.job-card-pdf', [
            'job'        => $job,
            'qrSvg'      => $qrSvg,
            'logoBase64' => $logoBase64,
        ])->setPaper('A4');

        // Footer
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $footerText = ($job->garage?->name ?? 'GarageSuite') . ' | ' . ($job->garage?->phone ?? '');
        $canvas->page_text(40, 820, $footerText, null, 9, [0.5, 0.5, 0.5]);
        $canvas->page_text(520, 820, "Page {PAGE_NUM} of {PAGE_COUNT}", null, 9, [0.5, 0.5, 0.5]);

        // ✅ Build storage path (per garage)
        $garageId = (int) $job->garage_id;
        $disk     = 'public';
        $path     = "garages/{$garageId}/job-cards/job-{$job->id}.pdf";

        $niceNumber = $job->job_number ?? ('JOB-' . $job->id);
        $filename   = "JobCard-{$niceNumber}.pdf";

        // ✅ Render PDF bytes and store (overwrite same path)
        $output = $pdf->output();
        Storage::disk($disk)->put($path, $output);

        $size = Storage::disk($disk)->size($path);

        // ✅ Versioning (increment)
        $existing = Document::where('garage_id', $garageId)
            ->where('documentable_type', Job::class)
            ->where('documentable_id', $job->id)
            ->where('document_type', 'job_card_pdf')
            ->first();

        $nextVersion = ($existing?->version ?? 0) + 1;

        // ✅ Archive row
        Document::updateOrCreate(
            [
                'garage_id'          => $garageId,
                'documentable_type'  => Job::class,
                'documentable_id'    => $job->id,
                'document_type'      => 'job_card_pdf',
            ],
            [
                'name'       => 'Job Card ' . $niceNumber,
                'disk'       => $disk,
                'path'       => $path,
                'file_name'  => $filename,
                'mime_type'  => 'application/pdf',
                'file_size'  => $size,
                'version'    => $nextVersion,
            ]
        );

        // ✅ Download from storage (same bytes you saved)
        return Storage::disk($disk)->download($path, $filename);
    }

    /*
    |--------------------------------------------------------------------------
    | Edit
    |--------------------------------------------------------------------------
    */
    public function edit(Job $job)
    {
        $this->authorizeGarage($job);

        if ($this->isLocked($job)) {
            return redirect()
                ->route('jobs.show', $job)
                ->with('info', 'This job is locked and cannot be edited.');
        }

        $garageId = Auth::user()->garage_id;

        $vehicles = Vehicle::with('customer')
            ->where('garage_id', $garageId)
            ->orderBy('registration_number')
            ->get();

        $job->load(['vehicle.customer', 'workItems', 'partItems', 'invoice']);

        $inventoryItems = InventoryItem::where('garage_id', $garageId)
            ->orderBy('name')
            ->get(['id', 'name', 'selling_price']);

        $partItems = old('part_items', $job->partItems->map(function ($item) {
            return [
                'description'       => $item->description,
                'quantity'          => $item->quantity,
                'unit_price'        => $item->unit_price,
                'line_total'        => $item->line_total,
                'inventory_item_id' => $item->inventory_item_id,
            ];
        })->values()->toArray());

        if (empty($partItems)) {
            $partItems = [
                ['description' => '', 'quantity' => null, 'unit_price' => null, 'line_total' => null, 'inventory_item_id' => null],
            ];
        }

        $inventoryForParts = $inventoryItems->map(fn ($i) => [
            'id'    => $i->id,
            'name'  => $i->name,
            'price' => $i->selling_price,
        ])->values()->toArray();

        return view('jobs.edit', [
            'job'               => $job,
            'vehicles'          => $vehicles,
            'statuses'          => $this->statuses(),
            'partItems'         => $partItems,
            'inventoryForParts' => $inventoryForParts,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Update status
    |--------------------------------------------------------------------------
    */
    public function updateStatus(Request $request, Job $job)
    {
        $this->authorizeGarage($job);

        $data = $request->validate([
            'status' => ['required', 'in:pending,in_progress,completed,cancelled'],
        ]);

        $from = (string) ($job->status ?? 'pending');
        $to   = (string) $data['status'];

        // ✅ Determine insurance context once
        $isInsurance = (string) ($job->payer_type ?? '') === 'insurance';
        $gate = app(InsuranceGate::class);

        // ✅ Insurance-only: status=in_progress implies repair start → must be allowed (pack approved etc.)
        if ($isInsurance && $to === 'in_progress') {
            $r = $gate->canStartRepair($job);
            if (!($r['ok'] ?? false)) {
                return back()->withErrors(['insurance_gate' => $r['reason'] ?? 'Repair cannot be started.']);
            }
        }

        // ✅ Insurance-only: cannot complete job unless repair is completed
        if ($isInsurance && $to === 'completed') {

            $latestRepair = \App\Models\JobRepair::query()
                ->where('garage_id', $job->garage_id)
                ->where('job_id', $job->id)
                ->latest('id')
                ->first();

            $repairDone = $latestRepair && (
                ($latestRepair->status ?? null) === 'completed' ||
                !is_null($latestRepair->completed_at)
            );

            if (! $repairDone) {
                return back()->withErrors([
                    'insurance_gate' => 'Cannot mark job as completed: repair is not completed yet.',
                ]);
            }
        }

        // ✅ Basic status transition rules
        $allowed = [
            'pending'     => ['in_progress', 'cancelled'],
            'in_progress' => ['completed', 'cancelled'],
            'completed'   => [],
            'cancelled'   => [],
        ];

        if (!in_array($to, $allowed[$from] ?? [], true)) {
            return back()->withErrors([
                'status' => "Invalid status change: {$from} → {$to}.",
            ]);
        }

        try {
            DB::transaction(function () use ($job, $to, $isInsurance) {

                // ✅ lock job row properly
                $lockedJob = Job::where('id', $job->id)
                    ->where('garage_id', $job->garage_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lockedJob->update(['status' => $to]);

                // ✅ If completed → ensure invoice exists and sync draft invoice
                // NOTE: If later you want insurance jobs to NOT auto-generate invoice here,
                // wrap this whole block in: if (!$isInsurance) { ... }
                if ($to === 'completed') {

                    $invoice = Invoice::where('garage_id', $lockedJob->garage_id)
                        ->where('job_id', $lockedJob->id)
                        ->lockForUpdate()
                        ->first();

                    if (! $invoice) {
                        $invoiceNumber = $this->generateInvoiceNumber($lockedJob->garage_id);

                        $invoice = Invoice::create([
                            'garage_id'      => $lockedJob->garage_id,
                            'job_id'         => $lockedJob->id,
                            'customer_id'    => $lockedJob->customer_id,
                            'vehicle_id'     => $lockedJob->vehicle_id,
                            'invoice_number' => $invoiceNumber,
                            'issue_date'     => now()->toDateString(),
                            'due_date'       => null,
                            'status'         => 'draft',
                            'payment_status' => 'unpaid',
                            'paid_amount'    => 0,
                            'subtotal'       => 0,
                            'tax_rate'       => $this->defaultTaxRateForJob($lockedJob), // ✅ 16%
                            'tax_amount'     => 0,
                            'total_amount'   => 0,
                            'currency'       => 'KES',
                        ]);
                    }

                    // ✅ always sync draft invoice from latest job snapshot
                    $this->syncDraftInvoiceFromJob($lockedJob);
                }
            });

            return back()->with('success', 'Job status updated.');
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            report($e);

            return back()->withErrors([
                'status' => 'Invoice number conflict occurred. Please try again.',
            ]);
        }
    }


    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */
    public function update(Request $request, Job $job)
    {
        $this->authorizeGarage($job);

        if ($this->isLocked($job)) {
            return redirect()
                ->route('jobs.show', $job)
                ->with('info', 'This job is locked and cannot be modified.');
        }

        $garageId = Auth::user()->garage_id;

        /**
         * ✅ PINPOINT FIX:
         * Your validateRequest() requires payer_type, but Edit form doesn't submit it.
         * Preserve payer_type from DB (saved from minute 1) unless the request explicitly sends it.
         */
        if (!$request->filled('payer_type')) {
            $request->merge([
                'payer_type' => $job->payer_type ?: 'individual',
            ]);
        }

        $data = $this->validateRequest($request);
        $data = $this->enforceUpdateStatus($job, $data);

        $gate = app(InsuranceGate::class);

        // If user is trying to add labour or parts, enforce repair gate (C-rule)
        $incomingLabour = (float) ($data['labour_cost'] ?? 0);
        $currentLabour  = (float) ($job->labour_cost ?? 0);

        $incomingParts  = $data['part_items'] ?? [];
        $hasIncomingParts = collect($incomingParts)->contains(function ($i) {
            $qty  = (float) ($i['quantity'] ?? 0);
            $unit = (float) ($i['unit_price'] ?? 0);
            $desc = trim((string) ($i['description'] ?? ''));
            $inv  = $i['inventory_item_id'] ?? null;

            // treat as a "real line" if qty+price and (desc or inventory link)
            return $qty > 0 && $unit > 0 && ($desc !== '' || !empty($inv));
        });

        $labourChangedUp = $incomingLabour > $currentLabour;
        $attemptingWork  = $labourChangedUp || $hasIncomingParts;

        $isInsurance = (string) ($job->payer_type ?? '') === 'insurance';

        if ($isInsurance && $attemptingWork) {
            $r = $gate->canAddLabourOrParts($job);
            if (!$r['ok']) {
                return back()->withErrors(['insurance_gate' => $r['reason']]);
            }
        }


        $vehicle = Vehicle::where('garage_id', $garageId)
            ->findOrFail($data['vehicle_id']);

        // ✅ SAFETY: vehicle->customer_id is the source of truth (prevents mismatch)
        $customerId = $vehicle->customer_id ?: ($data['customer_id'] ?? null);
        if (! $customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Customer is required for this vehicle.',
            ]);
        }

        $payerType = (string) ($data['payer_type'] ?? 'individual');
        $orgId     = $data['organization_id'] ?? null;

        DB::beginTransaction();

        try {
            $job->update([
                'vehicle_id'      => $vehicle->id,
                'customer_id'     => $customerId,

                'job_date'        => $data['job_date'],
                'service_type'    => $data['service_type'] ?? null,
                'mileage'         => $data['mileage'] ?? null,

                'complaint'       => $data['complaint'] ?? null,
                'diagnosis'       => $data['diagnosis'] ?? null,
                'notes'           => $data['notes'] ?? null,
                'work_done'       => $data['work_done'] ?? null,

                'status'          => $data['status'] ?? ($job->status ?? 'pending'),
                'labour_cost'     => (float) ($data['labour_cost'] ?? 0),

                // ✅ payer context
                'payer_type'      => $payerType,
                'organization_id' => $orgId,
            ]);

            $this->syncJobItems($job, [], $data['part_items'] ?? []);

            // ✅ Insurance details (keep in sync on edit)
            $this->syncInsuranceDetailsForJob($job->id, $payerType, $data['insurance'] ?? null);

            $this->syncDraftInvoiceFromJob($job);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return redirect()
            ->route('jobs.show', $job)
            ->with('success', 'Job updated successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Destroy
    |--------------------------------------------------------------------------
    */
    public function destroy(Job $job)
    {
        $this->authorizeGarage($job);

        $job->delete();

        return redirect()
            ->route('jobs.index')
            ->with('success', 'Job deleted successfully.');
    }

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */
    protected function validateRequest(Request $request): array
    {
        $request->merge([
            'status' => strtolower(trim((string) $request->input('status', 'pending')))
        ]);
        $garageId = Auth::user()->garage_id;

        $data = $request->validate([
            // ✅ Scope vehicle/customer to this garage (prevents cross-garage leaks)
            'vehicle_id'  => [
                'required',
                Rule::exists('vehicles', 'id')->where(fn ($q) => $q->where('garage_id', $garageId)),
            ],
            'customer_id' => [
                'nullable',
                Rule::exists('customers', 'id')->where(fn ($q) => $q->where('garage_id', $garageId)),
            ],

            'job_date'     => ['required', 'date'],
            'service_type' => ['nullable', 'string', 'max:255'],
            'mileage'      => ['nullable', 'integer'],

            'complaint' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'notes'     => ['nullable', 'string'],
            'work_done' => ['nullable', 'string'],

            'status' => ['nullable', 'in:pending,in_progress,completed,cancelled,draft'],            'labour_cost' => ['nullable', 'numeric', 'min:0'],

            // ✅ payer context
            'payer_type' => ['required', Rule::in(['individual', 'company', 'insurance'])],

            // org_id validated by invariant checks below (garage link + org.type)
            'organization_id' => ['nullable', 'integer'],

            // ✅ insurance detail payload (only meaningful when payer_type=insurance)
            'insurance'                 => ['nullable', 'array'],
            'insurance.policy_number'   => ['nullable', 'string', 'max:255'],
            'insurance.claim_number'    => ['nullable', 'string', 'max:255'],
            'insurance.excess_amount'   => ['nullable', 'numeric', 'min:0'],
            'insurance.adjuster_name'   => ['nullable', 'string', 'max:255'],
            'insurance.adjuster_phone'  => ['nullable', 'string', 'max:255'],
            'insurance.notes'           => ['nullable', 'string'],

            'part_items'                     => ['nullable', 'array'],
            'part_items.*.inventory_item_id' => [
                'nullable',
                Rule::exists('inventory_items', 'id')->where(fn ($q) => $q->where('garage_id', $garageId)),
            ],
            'part_items.*.description' => ['nullable', 'string'],
            'part_items.*.quantity'    => ['nullable', 'numeric', 'min:0'],
            'part_items.*.unit_price'  => ['nullable', 'numeric', 'min:0'],
        ]);

        if (($data['status'] ?? null) === 'draft') {
            $data['status'] = 'pending';
        }
        // ==========================
        // Phase 2 hard invariants
        // ==========================
        $payer = (string) ($data['payer_type'] ?? 'individual');

        // Normalize org_id
        $orgId = $data['organization_id'] ?? null;
        $orgId = $orgId !== null ? (int) $orgId : null;

        // Individual => force null org
        if ($payer === 'individual') {
            $data['organization_id'] = null;
            return $data;
        }

        // Company/Insurance => org is required
        if (! $orgId) {
            throw ValidationException::withMessages([
                'organization_id' => 'Organization is required for Company/Insurance jobs.',
            ]);
        }

        // Organization must be linked to this garage + correct type
        $org = Organization::query()
            ->join('garage_organizations', 'garage_organizations.organization_id', '=', 'organizations.id')
            ->where('garage_organizations.garage_id', $garageId)
            ->where('organizations.id', $orgId)
            ->select('organizations.id', 'organizations.type', 'organizations.status')
            ->first();

        if (! $org) {
            throw ValidationException::withMessages([
                'organization_id' => 'Invalid organization for this garage.',
            ]);
        }

        if (($org->status ?? 'inactive') !== 'active') {
            throw ValidationException::withMessages([
                'organization_id' => 'This organization is inactive.',
            ]);
        }

        if ($payer === 'company' && $org->type !== 'corporate') {
            throw ValidationException::withMessages([
                'organization_id' => 'Selected organization is not a corporate account.',
            ]);
        }

        if ($payer === 'insurance' && $org->type !== 'insurance') {
            throw ValidationException::withMessages([
                'organization_id' => 'Selected organization is not an insurance partner.',
            ]);
        }

        $data['organization_id'] = $orgId;

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Status Hard Rules
    |--------------------------------------------------------------------------
    */
    protected function enforceCreateStatus(array $data): array
    {
        $data['status'] = 'pending';
        return $data;
    }

    protected function enforceUpdateStatus(Job $job, array $data): array
    {
        $current   = (string) ($job->status ?? 'pending');
        $requested = (string) ($data['status'] ?? $current);

        // ✅ Normalize legacy 'draft' to behave like 'pending'
        if ($current === 'draft') {
            $current = 'pending';
        }
        if ($requested === 'draft') {
            $requested = 'pending';
        }

            $data['status'] = $requested;

        if (in_array($current, ['completed', 'cancelled'], true)) {
            if ($requested !== $current) {
                throw ValidationException::withMessages([
                    'status' => 'This job is locked and cannot be modified.',
                ]);
            }
            $data['status'] = $current;
            return $data;
        }

        $allowed = match ($current) {
            'pending'     => ['pending', 'in_progress', 'cancelled'],
            'in_progress' => ['in_progress', 'completed', 'cancelled'],
            default       => [$current],
        };

        if (!in_array($requested, $allowed, true)) {
            throw ValidationException::withMessages([
                'status' => "Invalid status change from {$current} to {$requested}.",
            ]);
        }

        $data['status'] = $requested;
        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Numbers
    |--------------------------------------------------------------------------
    */
    protected function generateJobNumber(int $garageId): string
    {
        $lastJob = Job::where('garage_id', $garageId)
            ->orderByDesc('id')
            ->first();

        $nextSequence = 1;

        if ($lastJob && $lastJob->job_number) {
            $parts = explode('-', $lastJob->job_number);
            $lastSeq = (int) end($parts);
            $nextSequence = $lastSeq + 1;
        }

        $garagePart = str_pad((string) $garageId, 4, '0', STR_PAD_LEFT);
        $yearPart   = now()->format('Y');
        $seqPart    = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        return "GS-{$garagePart}-{$yearPart}-{$seqPart}";
    }

    protected function generateInvoiceNumber(int $garageId): string
    {
        $last = Invoice::where('garage_id', $garageId)->orderByDesc('id')->first();

        $next = 1;
        if ($last && $last->invoice_number) {
            $num = preg_replace('/\D+/', '', $last->invoice_number);
            $next = ((int) $num) + 1;
        }

        return 'INV-' . str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    /*
    |--------------------------------------------------------------------------
    | VAT helpers (KES VAT = 16%)
    |--------------------------------------------------------------------------
    */
    protected function defaultTaxRateForJob(Job $job): float
    {
        return 16.00;
    }

    protected function calculateTax(float $subtotal, float $taxRate): float
    {
        return round($subtotal * ($taxRate / 100), 2);
    }

    protected function isLocked(Job $job): bool
    {
        return in_array(($job->status ?? 'pending'), ['completed', 'cancelled'], true);
    }

    /*
    |--------------------------------------------------------------------------
    | Draft Invoice Auto-Sync (only draft)
    |--------------------------------------------------------------------------
    */
    protected function syncDraftInvoiceFromJob(Job $job): void
    {
        $invoice = Invoice::where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->lockForUpdate()
            ->first();

        if (! $invoice) return;
        if (($invoice->status ?? 'draft') !== 'draft') return;

        $job->loadMissing(['partItems']);

        $invoice->items()->delete();

        $labourTotal = round((float) ($job->labour_cost ?? 0), 2);
        if ($labourTotal > 0) {
            $invoice->items()->create([
                'garage_id'   => $job->garage_id,
                'item_type'   => 'labour',
                'description' => 'Labour',
                'quantity'    => 1,
                'unit_price'  => $labourTotal,
                'line_total'  => $labourTotal,
            ]);
        }

        foreach ($job->partItems as $p) {
            $qty  = round((float) ($p->quantity ?? 0), 2);
            $unit = round((float) ($p->unit_price ?? 0), 2);
            $line = round((float) ($p->line_total ?? ($qty * $unit)), 2);

            if ($qty <= 0 || $unit <= 0) continue;

            $invoice->items()->create([
                'garage_id'   => $job->garage_id,
                'item_type'   => 'part',
                'description' => $p->description,
                'quantity'    => $qty,
                'unit_price'  => $unit,
                'line_total'  => $line,
            ]);
        }

        $subtotal = round((float) $invoice->items()->sum('line_total'), 2);

        $taxRate = (float) ($invoice->tax_rate ?? $this->defaultTaxRateForJob($job));
        $tax     = $this->calculateTax($subtotal, $taxRate);
        $total   = round($subtotal + $tax, 2);

        $invoice->update([
            'subtotal'     => $subtotal,
            'tax_rate'     => $taxRate,
            'tax_amount'   => $tax,
            'total_amount' => $total,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Sync Parts + Inventory Delta + Totals
    |--------------------------------------------------------------------------
    */
    protected function syncJobItems(Job $job, array $workItems, array $partItems): void
    {
        $garageId = $job->garage_id;

        $oldQtyByItem = $job->partItems()
            ->whereNotNull('inventory_item_id')
            ->selectRaw('inventory_item_id, SUM(quantity) as qty')
            ->groupBy('inventory_item_id')
            ->pluck('qty', 'inventory_item_id')
            ->map(fn ($v) => (float) $v)
            ->toArray();

        $newQtyByItem = [];
        foreach ($partItems as $item) {
            $invId = $item['inventory_item_id'] ?? null;
            $qty   = (float) ($item['quantity'] ?? 0);

            if ($invId && $qty > 0) {
                $newQtyByItem[$invId] = ($newQtyByItem[$invId] ?? 0) + $qty;
            }
        }

        $allInvIds = array_values(array_unique(array_merge(array_keys($oldQtyByItem), array_keys($newQtyByItem))));

        foreach ($allInvIds as $invId) {
            $old = (float) ($oldQtyByItem[$invId] ?? 0);
            $new = (float) ($newQtyByItem[$invId] ?? 0);
            $delta = $new - $old;

            if ($delta == 0.0) continue;

            $inv = InventoryItem::where('garage_id', $garageId)
                ->lockForUpdate()
                ->find($invId);

            if (! $inv) continue;

            if ($delta > 0) {
                $available = (float) ($inv->current_stock ?? 0);

                if ($available < $delta) {
                    throw ValidationException::withMessages([
                        'part_items' => "Insufficient stock for {$inv->name}. Available: {$available}, needed extra: {$delta}.",
                    ]);
                }

                $inv->decrement('current_stock', $delta);
                $this->recordInventoryMovementIfAvailable($inv->id, $garageId, 'out', $delta, 'Used in job', $job->id);
            } else {
                $returnQty = abs($delta);
                $inv->increment('current_stock', $returnQty);
                $this->recordInventoryMovementIfAvailable($inv->id, $garageId, 'in', $returnQty, 'Returned from job edit', $job->id);
            }
        }

        $job->workItems()->delete();
        $job->partItems()->delete();

        $partsTotal = 0.0;

        foreach ($partItems as $item) {
            $description     = trim($item['description'] ?? '');
            $inventoryItemId = $item['inventory_item_id'] ?? null;
            $quantity        = (float) ($item['quantity'] ?? 0);
            $unitPrice       = (float) ($item['unit_price'] ?? 0);

            if ($inventoryItemId && $description === '') {
                $inv = InventoryItem::where('garage_id', $garageId)->find($inventoryItemId);
                if ($inv) {
                    $description = $inv->name;
                    if ($unitPrice <= 0) {
                        $unitPrice = (float) $inv->selling_price;
                    }
                }
            }

            if ($description === '' && ($quantity <= 0 || $unitPrice <= 0)) {
                continue;
            }

            $lineTotal = $quantity * $unitPrice;

            $job->partItems()->create([
                'inventory_item_id' => $inventoryItemId,
                'description'       => $description,
                'quantity'          => $quantity,
                'unit_price'        => $unitPrice,
                'line_total'        => $lineTotal,
            ]);

            $partsTotal += $lineTotal;
        }

        $labourTotal = (float) ($job->labour_cost ?? 0);
        $finalTotal  = $labourTotal + $partsTotal;

        $job->update([
            'parts_cost' => $partsTotal,
            'final_cost' => $finalTotal,
        ]);
    }

    protected function recordInventoryMovementIfAvailable(
        int $inventoryItemId,
        int $garageId,
        string $type,
        float $quantity,
        string $reason,
        int $jobId
    ): void {
        $model = \App\Models\InventoryItemMovement::class;

        if (!class_exists($model)) return;

        $model::create([
            'garage_id'         => $garageId,
            'inventory_item_id' => $inventoryItemId,
            'type'              => $type,
            'quantity'          => $quantity,
            'reason'            => $reason,
            'job_id'            => $jobId,
            'created_by'        => Auth::id(),
        ]);
    }

    protected function statuses(): array
    {
        return ['pending', 'in_progress', 'completed', 'cancelled'];
    }

    protected function authorizeGarage(Job $job): void
    {
        if (!$job) {
            abort(404, 'Job not found');
        }

        $user = Auth::user();
        if (!$user) {
            abort(403, 'User is not authenticated');
        }

        // ✅ Use tenant context first (impersonation/session), fallback to user's home garage
        $currentGarageId = (int) (session('impersonated_garage_id')
            ?? session('garage_id')
            ?? $user->garage_id);

        Log::info('Authorizing Garage Access', [
            'job_id'             => $job->id,
            'job_garage_id'      => (int) $job->garage_id,
            'user_id'            => $user->id,
            'user_garage_id'     => (int) $user->garage_id,
            'current_garage_id'  => $currentGarageId,
            'impersonated'       => (bool) session('impersonated_garage_id'),
        ]);

        abort_unless((int) $job->garage_id === $currentGarageId, 403, 'Unauthorized access to this job.');
    }


    /*
    |--------------------------------------------------------------------------
    | Helpers (Phase 2 additions)
    |--------------------------------------------------------------------------
    */

    /**
     * Returns [corporateOrgs, insuranceOrgs] linked to this garage.
     */
    protected function orgListsForGarage(int $garageId): array
    {
        $base = Organization::query()
            ->join('garage_organizations', 'garage_organizations.organization_id', '=', 'organizations.id')
            ->where('garage_organizations.garage_id', $garageId)
            ->where('organizations.status', 'active')
            ->select('organizations.id', 'organizations.name', 'organizations.type');

        $corporateOrgs = (clone $base)
            ->where('organizations.type', 'corporate')
            ->orderBy('organizations.name')
            ->get(['organizations.id', 'organizations.name']);

        $insuranceOrgs = (clone $base)
            ->where('organizations.type', 'insurance')
            ->orderBy('organizations.name')
            ->get(['organizations.id', 'organizations.name']);

        return [$corporateOrgs, $insuranceOrgs];
    }

    /**
     * Keep job_insurance_details 1:1 in sync with payer type.
     * (We keep legacy insurer_name as NULL and rely on jobs.organization_id.)
     */
    protected function syncInsuranceDetailsForJob(int $jobId, string $payerType, ?array $insurance): void
    {
        if ($payerType === 'insurance') {
            $insurance = $insurance ?? [];

            $garageId = DB::table('jobs')->where('id', $jobId)->value('garage_id');

            DB::table('job_insurance_details')->updateOrInsert(
                ['job_id' => $jobId],
                [
                    'garage_id'      => $garageId,
                    'insurer_id'     => $insurance['insurer_id']     ?? null,
                    'insurer_name'   => $insurance['insurer_name']   ?? null,
                    'policy_number'  => $insurance['policy_number']  ?? null,
                    'claim_number'   => $insurance['claim_number']   ?? null,
                    'lpo_number'     => $insurance['lpo_number']     ?? null,
                    'excess_amount'  => $insurance['excess_amount']  ?? null,
                    'adjuster_name'  => $insurance['adjuster_name']  ?? null,
                    'adjuster_phone' => $insurance['adjuster_phone'] ?? null,
                    'notes'          => $insurance['notes']          ?? null,
                    'updated_at'     => now(),
                    // created_at handled below (only if new)
                    'created_at'     => now(),
                ]
            );

            return;
        }

        DB::table('job_insurance_details')->where('job_id', $jobId)->delete();
    }


    public function insuranceShow(Job $job)
    {
        // ✅ Always authorize FIRST (and do it before logging $job props in case binding fails)
        if (! $job || ! data_get($job, 'id')) {
            abort(404, 'Job not found');
        }

        $this->authorizeGarage($job);

        $garageId = auth()->user()->garage_id;

        // ✅ Load core relations used in header/cards
        $job->load(['approval', 'vehicle.customer', 'customer', 'vehicle', 'invoice']);

        // ---------------------------------
        // ✅ Approval Pack (latest)
        // ---------------------------------
        $pack = \App\Models\ApprovalPack::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        $packShareUrl = null;

        if ($pack) {
            try {
                $packShareUrl = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                    'insurance.approval-packs.share',
                    now()->addDays(14),
                    ['pack' => $pack->id]
                );
            } catch (\Throwable $e) {
                \Log::warning('Pack share url generation failed', [
                    'job_id' => $job->id,
                    'pack_id' => $pack->id,
                    'err' => $e->getMessage(),
                ]);
                $packShareUrl = null;
            }
        }


        // ---------------------------------
        // ✅ Job-centric Inspection
        // ---------------------------------
        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
            ->orderByDesc('id')
            ->first();

        if (! $inspection) {
            $inspection = \App\Models\JobInspection::create([
                'garage_id' => $garageId,
                'job_id'    => $job->id,
                'type'      => 'check_in',
                'status'    => 'draft',
            ]);
        }

        // ✅ Phase 1 sync: jobs.inspection_completed_at is DB truth for the whole workflow
        if ($inspection->status === 'completed' && empty($job->inspection_completed_at)) {
            $job->forceFill([
                'inspection_completed_at' => $inspection->completed_at ?? now(),
            ])->save();

            $job->refresh();
        }

        // ---------------------------------
        // ✅ Inspection Photos
        // ---------------------------------
        $attachments = \App\Models\MediaAttachment::query()
            ->with('mediaItem')
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo')
            ->orderByDesc('id')
            ->get();

        $attached = $attachments->map(function ($a) {
            $m = $a->mediaItem;
            $disk = $m->disk ?: 'public';

            return [
                'id'            => $m?->id,
                'attachment_id' => $a->id,
                'url'       => $m ? \Storage::disk($disk)->url($m->path) : null,
                'thumb_url' => $m ? \Storage::disk($disk)->url($m->path) : null,
                ];
        })->values()->toArray();

        $photosCount = count($attached);

        // Checklist done count
        $doneItems = \App\Models\JobInspectionItem::query()
            ->where('garage_id', $garageId)
            ->where('inspection_id', $inspection->id)
            ->whereIn('state', ['ok', 'damaged', 'missing'])
            ->count();

        // ---------------------------------
        // ✅ Quotation (latest)
        // ---------------------------------
        $quote = \App\Models\JobQuotation::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        $quoteLines = $quote
            ? \App\Models\JobQuotationLine::query()
                ->where('quotation_id', $quote->id)
                ->where(function ($q) use ($garageId) {
                    $q->where('garage_id', $garageId)
                    ->orWhereNull('garage_id'); // ✅ temporary safety for legacy rows
                })
                ->orderBy('id')
                ->get()
            : collect();

        // ✅ ctx used by the inspection/quotation card routes
        $ctx = ['job' => $job->id];

        // ---------------------------------
        // ✅ Repair Execution (Phase 5: safest possible)
        // - Never assume relationships exist
        // - Never crash if no repair exists
        // - Always return predictable defaults
        // ---------------------------------

        $repairSession = \App\Models\JobRepair::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        $repairItems = collect();

        if ($repairSession) {
            $repairItems = \App\Models\JobRepairItem::query()
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repairSession->id)
                ->orderBy('id')
                ->get();
        }

        // ✅ Normalize unknown/legacy statuses to "pending" when counting
        $safeExec = function ($v) {
            return in_array($v, ['pending', 'in_progress', 'done', 'skipped'], true) ? $v : 'pending';
        };

        $repairStats = [
            'total'       => $repairItems->count(),
            'pending'     => $repairItems->filter(fn ($i) => $safeExec($i->execution_status) === 'pending')->count(),
            'in_progress' => $repairItems->filter(fn ($i) => $safeExec($i->execution_status) === 'in_progress')->count(),
            'done'        => $repairItems->filter(fn ($i) => $safeExec($i->execution_status) === 'done')->count(),
            'skipped'     => $repairItems->filter(fn ($i) => $safeExec($i->execution_status) === 'skipped')->count(),
        ];

        // ---------------------------------
        // ✅ Phase 2: Build gates ONCE (DB truth only)
        // ---------------------------------
        $gates = app(\App\Services\InsuranceGate::class)->forJob($job);

        $invoice = $job->invoice()
        ->select(['id','garage_id','job_id','invoice_number','total_amount','paid_amount','payment_status','paid_at'])
        ->first(); // one invoice per job
        $payments = collect();

        if ($invoice) {
            $payments = \App\Models\Payment::query()
                ->where('garage_id', $garageId)
                ->where('invoice_id', $invoice->id)
                ->orderByDesc('id')
                ->get();
        }

        return view('jobs.insurance.show', [
            'job'           => $job,
            'gates'         => $gates,
            'ctx'           => $ctx,

            // Inspection
            'inspection'    => $inspection,
            'attached'      => $attached,
            'photosCount'   => $photosCount,
            'doneItems'     => $doneItems,

            // Approval Pack
            'pack'          => $pack,
            'packShareUrl'  => $packShareUrl,

            // Quotation
            'quote'         => $quote,
            'quoteLines'    => $quoteLines,

            // Repair
            'repairSession' => $repairSession,
            'repairItems'   => $repairItems,
            'repairStats'   => $repairStats,
            'invoice'   => $invoice,
            'payments'  => $payments,
        ]);

    }

    
    public function quotationSave(Request $request)
    {
        $garageId = auth()->user()->garage_id;

        $data = $request->validate([
            'job_id'       => ['required', 'integer'],
            'lines'        => ['nullable', 'array'],
            'lines.*.type'        => ['nullable', Rule::in(['labour','parts','materials','sublet'])],
            'lines.*.category'    => ['nullable', 'string', 'max:100'],
            'lines.*.description' => ['nullable', 'string', 'max:255'],
            'lines.*.qty'         => ['nullable', 'numeric', 'min:0'],
            'lines.*.unit_price'  => ['nullable', 'numeric', 'min:0'],
            'lines.*.amount'      => ['nullable', 'numeric', 'min:0'],

            // VAT toggle
            'vat_enabled' => ['nullable', 'boolean'],
            'vat_rate'    => ['nullable', 'numeric', 'min:0', 'max:1'],

            'discount'    => ['nullable', 'numeric', 'min:0'],
            'action'      => ['nullable', Rule::in(['save','submit'])],
        ]);

        $job = Job::query()
            ->where('garage_id', $garageId)
            ->whereKey((int) $data['job_id'])
            ->firstOrFail();

        // ✅ must be insurance job if you want
        // abort_unless(($job->payer_type ?? null) === 'insurance', 403);

        $lines = $data['lines'] ?? [];
        $vatEnabled = (bool) ($data['vat_enabled'] ?? true);
        $vatRate    = (float) ($data['vat_rate'] ?? 0.16);
        $discount   = round((float) ($data['discount'] ?? 0), 2);
        $action     = (string) ($data['action'] ?? 'save');

        return DB::transaction(function () use (
            $garageId, $job, $lines, $vatEnabled, $vatRate, $discount, $action
        ) {
            // 1) Quote row
            $quote = \App\Models\JobQuotation::query()
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

            if (! $quote) {
                $quote = \App\Models\JobQuotation::create([
                    'garage_id' => $garageId,
                    'job_id'    => $job->id,
                    'status'    => 'draft',
                    'version'   => 1,
                    'subtotal'  => 0,
                    'tax'       => 0,
                    'discount'  => 0,
                    'total'     => 0,
                ]);
            }

            // 2) Normalize + compute line amounts (qty * unit_price unless amount provided)
            $clean = [];
            foreach ($lines as $idx => $l) {
                $qty  = round((float) ($l['qty'] ?? 0), 2);
                $unit = round((float) ($l['unit_price'] ?? 0), 2);

                // amount: allow manual amount, but if missing, compute it
                $amount = array_key_exists('amount', $l)
                    ? round((float) ($l['amount'] ?? 0), 2)
                    : round($qty * $unit, 2);

                $desc = trim((string) ($l['description'] ?? ''));

                // skip completely empty lines
                if ($desc === '' && $qty <= 0 && $unit <= 0 && $amount <= 0) {
                    continue;
                }

                $clean[] = [
                    'garage_id'    => $garageId,          // ✅ IMPORTANT
                    'quotation_id' => $quote->id,
                    'type'         => $l['type'] ?? 'labour',
                    'category'     => $l['category'] ?? null,
                    'description'  => $desc ?: '—',
                    'qty'          => $qty > 0 ? $qty : 1,
                    'unit_price'   => $unit,
                    'amount'       => $amount,
                    'sort_order'   => (int) $idx,
                ];
            }

            // 3) Persist lines (simple + safe: delete & recreate)
            \App\Models\JobQuotationLine::query()
                ->where('quotation_id', $quote->id)
                ->where(function ($q) use ($garageId) {
                    $q->where('garage_id', $garageId)->orWhereNull('garage_id');
                })
                ->delete();

            foreach ($clean as $row) {
                \App\Models\JobQuotationLine::create($row);
            }

            // 4) Totals
            $subtotal = round(collect($clean)->sum(fn ($r) => (float) $r['amount']), 2);
            $tax      = $vatEnabled ? round($subtotal * $vatRate, 2) : 0.00;
            $total    = max(0, round($subtotal + $tax - $discount, 2));

            $quote->forceFill([
                'subtotal' => $subtotal,
                'tax'      => $tax,
                'discount' => $discount,
                'total'    => $total,
                'status'   => $quote->status ?? 'draft',
            ])->save();

            // 5) Submit (optional)
            if ($action === 'submit') {
                $quote->forceFill([
                    'status'       => 'submitted',
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                ])->save();
            }

            return response()->json([
                'ok'        => true,
                'quote_id'  => $quote->id,
                'status'    => $quote->status,
                'saved_at'  => now()->toISOString(),
                'subtotal'  => $quote->subtotal,
                'tax'       => $quote->tax,
                'discount'  => $quote->discount,
                'total'     => $quote->total,
            ]);
        });
    }

    // ------------------------------------------------------
    // Insurance Vault endpoints (for Inspection photos)
    // Routes already exist in route:list and point here.
    // ------------------------------------------------------

    public function insuranceVaultPicker(Job $job, Request $request)
    {
        // If no attach mode was provided, treat this as "open vault",
        // not "picker". Send user to the main Vault page UI.
        $attachMode = $request->get('attach') ?: $request->get('mode');

        if (! $attachMode) {
            return redirect()->route('vault.index');
        }

        $attachMode = $attachMode ?: 'inspection'; 
        $returnUrl  = request('return');

        // Attach target depends on attach mode
        if ($attachMode === 'completion') {
            $attachAction = route('jobs.insurance.claim.completion-photos.attach', $job->id);
        } else {
            $attachAction = route('jobs.insurance.vault.attach', $job->id);
        }
        $this->authorizeGarage($job);

        $garageId = auth()->user()->garage_id;

        // ensure inspection exists
        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->orderByDesc('id')
            ->first();

        if (! $inspection) {
            $inspection = \App\Models\JobInspection::create([
                'garage_id' => $garageId,
                'job_id'    => $job->id,
                'type'      => 'check_in',
                'status'    => 'draft',
            ]);
        }

        // pull vault items (images) for this garage
        $vaultItems = \App\Models\MediaItem::query()
            ->where('garage_id', $garageId)
            ->latest('id')
            ->paginate(24);

        // Render the picker blade into HTML first
        $html = view('jobs.insurance.inspection.vault-picker', [
            'job'          => $job,
            'inspection'   => $inspection,
            'vaultItems'   => $vaultItems,
            'items'        => $vaultItems, // blade expects $items

            // ✅ ADD THESE
            'attachMode'   => $attachMode,
            'attachAction' => $attachAction,
            'returnUrl'    => $returnUrl,
        ])->render();


        // If this is an AJAX/JSON request (which inspection.js sends),
        // return JSON in the format the JS expects.
        if ($request->expectsJson() || $request->ajax()) {
            return response()->json(['html' => $html]);
        }

        // Fallback (in case someone opens the URL directly in browser)
        return view('jobs.insurance.inspection.vault-picker-page', [
            'job'          => $job,
            'inspection'   => $inspection,
            'vaultItems'   => $vaultItems,
            'items'        => $vaultItems,
            'attachMode'   => $attachMode,
            'attachAction' => $attachAction,
            'returnUrl'    => $returnUrl,
        ]);
    }

    public function insuranceVaultUpload(Job $job, Request $request)
    {
        $this->authorizeGarage($job);

        $garageId = auth()->user()->garage_id;

        $request->validate([
            'file' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        $file = $request->file('file');

        $disk = 'public';
        $path = $file->store("garages/{$garageId}/vault", $disk);

        $media = \App\Models\MediaItem::create([
            'garage_id'  => $garageId,
            'disk'       => $disk,
            'path'       => $path,
            'mime_type'  => $file->getMimeType(),
            'file_name'  => $file->getClientOriginalName(),
            'file_size'  => $file->getSize(),
            'created_by' => auth()->id(),
        ]);

        return response()->json([
            'ok'      => true,
            'media_id'=> $media->id,
            'url'     => \Storage::disk($disk)->url($path),
        ]);
    }

    public function vaultAttachToInspection(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $garageId = (int) auth()->user()->garage_id;

        $jobId = (int) ($request->input('job_id') ?: $request->input('job'));
        if (!$jobId) {
            return response()->json(['ok' => false, 'message' => 'Missing job_id.'], 422);
        }

        $label = (string) $request->input('label', 'inspection'); // inspection | completion

        $request->validate([
            'media_item_ids' => ['required', 'array', 'min:1'],
            'media_item_ids.*' => ['integer'],
        ]);

        $ids = array_values(array_unique(array_map('intval', (array) $request->input('media_item_ids', []))));

        // Ensure media items belong to this garage
        $validIds = \App\Models\MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        if ($label === 'completion') {

            // ✅ Gate: require repair session exists (keep your rule)
            $repair = \DB::table('job_repairs')
                ->where('garage_id', $garageId)
                ->where('job_id', $jobId)
                ->orderByDesc('id')
                ->first();

            if (!$repair) {
                return response()->json([
                    'ok' => false,
                    'message' => 'Start a repair session before adding completion photos.',
                ], 422);
            }

            // ✅ Attach to JOB (not repair) with label=completion
            \DB::transaction(function () use ($garageId, $jobId, $validIds) {
                foreach ($validIds as $mid) {
                    \DB::table('media_attachments')->updateOrInsert(
                        [
                            'garage_id'       => $garageId,
                            'media_item_id'   => (int) $mid,
                            'attachable_type' => \App\Models\Job::class,
                            'attachable_id'   => (int) $jobId,
                            'label'           => 'completion',
                        ],
                        [
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }
            });

            $attached = \DB::table('media_attachments as ma')
                ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
                ->where('ma.garage_id', $garageId)
                ->where('ma.attachable_type', \App\Models\Job::class)
                ->where('ma.attachable_id', (int) $jobId)
                ->where('ma.label', 'completion')
                ->orderByDesc('ma.id')
                ->select('mi.*', 'ma.id as attachment_id', 'ma.label')
                ->get();

            return response()->json([
                'ok' => true,
                'message' => 'Completion photo(s) attached.',
                'photos_count' => $attached->count(),
                'attached' => $attached,
            ]);
        }

        // ----------------------------
        // ✅ Default: Inspection attach (existing behavior)
        // ----------------------------

        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $garageId)
            ->where('job_id', $jobId)
            ->orderByDesc('id')
            ->first();

        if (!$inspection) {
            $inspection = \App\Models\JobInspection::create([
                'garage_id' => $garageId,
                'job_id'    => $jobId,
                'type'      => 'check_in',
                'status'    => 'draft',
            ]);
        }

        \DB::transaction(function () use ($garageId, $inspection, $validIds) {
            foreach ($validIds as $mid) {
                \DB::table('media_attachments')->updateOrInsert(
                    [
                        'garage_id'       => $garageId,
                        'media_item_id'   => (int) $mid,
                        'attachable_type' => \App\Models\JobInspection::class,
                        'attachable_id'   => (int) $inspection->id,
                        'label'           => 'inspection_photo', // ← REQUIRED by Approval
                    ],
                    [
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        $attached = \DB::table('media_attachments as ma')
            ->join('media_items as mi', 'mi.id', '=', 'ma.media_item_id')
            ->where('ma.garage_id', $garageId)
            ->where('ma.attachable_type', \App\Models\JobInspection::class)
            ->where('ma.attachable_id', (int) $inspection->id)
            ->orderByDesc('ma.id')
            ->select('mi.*', 'ma.id as attachment_id')
            ->get();

        return response()->json([
            'ok' => true,
            'message' => 'Photo(s) attached.',
            'photos_count' => $attached->count(),
            'attached' => $attached,
        ]);
    }
    public function insuranceVaultDetachFromInspection(Job $job, Request $request)
    {
        $this->authorizeGarage($job);

        $request->merge(['job' => $job->id,'job_id' => $job->id]);

        return $this->wizard()->vaultDetachFromInspection($request);
    }

    public function insuranceVaultAttachToInspection(Request $request, \App\Models\Job $job)
    {
        // ✅ Authorize access as you already do elsewhere (keep your existing guard)
        // If you already have a method for this, call it instead.
        if (method_exists($this, 'authorizeGarageAccess')) {
            $this->authorizeGarageAccess($job);
        }

        $data = $request->validate([
            'media_item_id' => ['required', 'integer'],
            // allow either inspection or completion (default to completion if not sent)
            'attach'        => ['nullable', 'string', 'in:inspection,completion'],
            'label'         => ['nullable', 'string', 'max:255'],
            'return'        => ['nullable', 'string'],
        ]);

        $garageId = (int) (auth()->user()->garage_id ?? 0);
        $mediaItemId = (int) $data['media_item_id'];
        $attach = $data['attach'] ?? 'completion';

        // ✅ Ensure media item belongs to same garage (critical)
        $media = DB::table('media_items')
            ->where('id', $mediaItemId)
            ->where('garage_id', $garageId)
            ->first();

        abort_unless($media, 404, 'Media item not found for this garage.');

        // ✅ Decide attachable target
        // We attach to the Job itself, but differentiate category via label.
        // If you already use a different attachable_type, swap it here.
        $attachableType = \App\Models\Job::class;
        $attachableId   = $job->id;

        // ✅ Build a deterministic label
        // Examples: "inspection", "completion", or "completion:front-bumper"
        $base = $attach; // inspection|completion
        $suffix = trim((string)($data['label'] ?? ''));
        $label = $suffix ? ($base . ':' . $suffix) : $base;

        // ✅ Prevent duplicates (same media, same job, same label)
        $exists = DB::table('media_attachments')
            ->where('garage_id', $garageId)
            ->where('media_item_id', $mediaItemId)
            ->where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->where('label', $label)
            ->exists();

        if (!$exists) {
            DB::table('media_attachments')->insert([
                'garage_id'       => $garageId,
                'media_item_id'   => $mediaItemId,
                'attachable_type' => $attachableType,
                'attachable_id'   => $attachableId,
                'label'           => $label,
                'created_at'      => now(),
                'updated_at'      => now(),
            ]);
        }

        // ✅ Redirect back
        $return = $data['return'] ?? url()->previous();

        return redirect($return)->with('success', 'Photo attached to ' . $attach . '.');
    }
    /*
    |--------------------------------------------------------------------------
    | Insurance Inspection Bridge (reuses Wizard Logic)
    |--------------------------------------------------------------------------
    | We moved routes from JobCreateWizardController → JobController,
    | but the logic still lives there. These proxy methods call it.
    */

    protected function wizard()
    {
        return app(\App\Http\Controllers\Jobs\JobCreateWizardController::class);
    }

    public function insuranceInspectionChecklistLoad(\App\Models\Job $job, \Illuminate\Http\Request $request)
    {
        $this->authorizeGarage($job);

        $inspection = \App\Models\JobInspection::firstOrCreate(
            ['garage_id' => $job->garage_id, 'job_id' => $job->id],
            [
                'type'       => 'check_in',
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]
        );

        // ✅ Wizard expects `job` (NOT job_id)
        $request->merge([
            'job'           => $job->id,
            'job_id'        => $job->id,          // keep compatibility
            'inspection_id' => $inspection->id,
        ]);

        // ✅ Correct method name
        return $this->wizard()->inspectionChecklistLoad($request);
    }


    public function insuranceInspectionChecklistSave(\App\Models\Job $job, \Illuminate\Http\Request $request)
    {
        $this->authorizeGarage($job);

        $inspection = \App\Models\JobInspection::firstOrCreate(
            ['garage_id' => $job->garage_id, 'job_id' => $job->id],
            [
                'type'       => 'check_in',
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]
        );

        $request->merge([
            'job'           => $job->id,
            'job_id'        => $job->id,
            'inspection_id' => $inspection->id,
        ]);

        return $this->wizard()->inspectionChecklistSave($request);
    }

    public function insuranceInspectionSave(\App\Models\Job $job, \Illuminate\Http\Request $request)
    {
        $this->authorizeGarage($job);

        $inspection = \App\Models\JobInspection::firstOrCreate(
            ['garage_id' => $job->garage_id, 'job_id' => $job->id],
            [
                'type'       => 'check_in',
                'status'     => 'draft',
                'created_by' => auth()->id(),
            ]
        );

        $request->merge([
            'job' => $job->id,
            'job_id'        => $job->id,
            'inspection_id' => $inspection->id,
        ]);

        return $this->wizard()->inspectionSave($request);
    }


    public function insuranceInspectionComplete(\App\Models\Job $job, \Illuminate\Http\Request $request)
    {
        $this->authorizeGarage($job);

        $inspection = \App\Models\JobInspection::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->firstOrFail();

        // ✅ DB truth: count inspection photos from media_attachments (Job scoped)
        $photosCount = 0;
        if (\Schema::hasTable('media_attachments')) {
            $photosCount = (int) \DB::table('media_attachments')
                ->where('garage_id', (int) $job->garage_id)
                ->where('attachable_type', \App\Models\Job::class)
                ->where('attachable_id', (int) $job->id)
                ->where('label', 'inspection')
                ->distinct('media_item_id')
                ->count('media_item_id');
        }

        // ✅ Force wizard context as before + inject photos_count so wizard doesn't see 0
        $request->merge([
            'job'           => $job->id,
            'job_id'        => $job->id,
            'inspection_id' => $inspection->id,

            // these two keys cover most implementations
            'photos_count'  => $photosCount,
            'photosCount'   => $photosCount,
        ]);

        return $this->wizard()->inspectionComplete($request);
    }

    protected function enforceInsuranceContext(Request $request, array &$draft): void
    {
        if (($draft['payer_type'] ?? null) === 'insurance') return;

        $garageId = auth()->user()->garage_id;

        // Wizard uses `job` as primary job context
        $jobId = (int) ($request->input('job') ?: $request->input('job_id'));

        if ($jobId > 0) {
            $job = \App\Models\Job::query()
                ->where('garage_id', $garageId)
                ->whereKey($jobId)
                ->first();

            if (($job->payer_type ?? null) === 'insurance') {
                // Hydrate draft context so existing guards + logic work
                $draft['payer_type'] = 'insurance';
                $this->putDraft($request, $draft);
                return;
            }
        }

        abort(403);
    }

}

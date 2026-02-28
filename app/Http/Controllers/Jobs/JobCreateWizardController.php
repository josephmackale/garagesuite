<?php

namespace App\Http\Controllers\Jobs;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

use App\Models\JobInspection;
use App\Models\JobInspectionItem;
use Illuminate\Support\Str;
use App\Models\MediaItem;
use App\Models\MediaAttachment;
use App\Models\JobDraft;
use App\Services\JobDraftService;
use App\Models\JobQuotation;
use App\Models\JobQuotationLine;
use App\Services\InsuranceGate;
use App\Models\ApprovalPack;

class JobCreateWizardController extends Controller
{


    private const SESSION_KEY = 'job_create_draft';

    /**
     * Get current draft_uuid: query > input > session pointer
     * Supports both ?draft= and ?draft_uuid=
     */
    private function draftUuid(Request $request): ?string
    {
        return $request->query('draft')
            ?? $request->input('draft')
            ?? $request->query('draft_uuid')
            ?? $request->input('draft_uuid')
            ?? ($request->session()->get(self::SESSION_KEY)['draft_uuid'] ?? null);
    }


    /**
     * Ensure we have a JobDraft row for this wizard session.
     * Session stores ONLY the pointer (draft_uuid).
     */
    private function ensureDbDraft(Request $request): JobDraft
    {
        $garageId = auth()->user()->garage_id;
        $userId   = auth()->id();

        /**
         * Platform admin cannot run tenant flows.
         * Must impersonate first.
         */
        if (empty($garageId)) {
            abort(403, 'Platform Admin must impersonate a garage before creating jobs.');
        }



        $uuid = $this->draftUuid($request) ?: (string) Str::uuid();

        $draft = JobDraft::query()
            ->where('garage_id', $garageId)
            ->where('draft_uuid', $uuid)
            ->first();

        if (!$draft) {
            $draft = JobDraft::create([
                'garage_id'  => $garageId,
                'user_id'    => $userId,
                'draft_uuid' => $uuid,
                'status'     => 'draft',
                'last_step'  => 'step1',
            ]);
        }

        // pointer only
        $request->session()->put(self::SESSION_KEY, ['draft_uuid' => $draft->draft_uuid]);

        return $draft;
    }

    /**
     * READ draft (DB-backed)
     */
    private function draft(Request $request): array
    {
        $d = $this->ensureDbDraft($request);

        $details = is_array($d->details ?? null) ? $d->details : [];
        $payer   = is_array($d->payer ?? null) ? $d->payer : [];

        return [
            'customer_id' => $d->customer_id,
            'vehicle_id'  => $d->vehicle_id,

            // modal stays request-based
            'modal'       => $request->query('modal') ?? $request->input('modal') ?? null,

            'job_id'      => $d->job_id,
            'last_job_id' => $d->job_id,

            'payer_type'  => $d->payer_type,
            'payer'       => $payer,
            'details'     => $details,

            'draft_uuid'  => $d->draft_uuid,

            // keep compatibility with existing inspection anchor
            'inspection_id' => $details['inspection_id'] ?? null,
        ];
    }

    /**
     * WRITE draft (DB-backed)
     */
    private function putDraft(Request $request, array $draft): void
    {
        $d = $this->ensureDbDraft($request);

        if (array_key_exists('customer_id', $draft)) $d->customer_id = $draft['customer_id'];
        if (array_key_exists('vehicle_id',  $draft)) $d->vehicle_id  = $draft['vehicle_id'];
        if (array_key_exists('payer_type',  $draft)) $d->payer_type  = $draft['payer_type'];
        if (array_key_exists('job_id', $draft)) $d->job_id = $draft['job_id'];

        if (array_key_exists('payer', $draft)) {
            $d->payer = is_array($draft['payer']) ? $draft['payer'] : [];
        }

        if (array_key_exists('details', $draft)) {
            $d->details = is_array($draft['details']) ? $draft['details'] : [];
        }

        /**
         * ✅ HARD NORMALIZE inspection_id
         * It may come in as:
         * - $draft['inspection_id'] (root)
         * - $draft['details']['inspection_id'] (preferred / DB)
         */
        $incomingInspectionId = null;

        if (!empty($draft['inspection_id'])) {
            $incomingInspectionId = (int) $draft['inspection_id'];
        } elseif (!empty($draft['details']['inspection_id'])) {
            $incomingInspectionId = (int) $draft['details']['inspection_id'];
        }

        // ✅ persist inspection_id into details ALWAYS when present
        if ($incomingInspectionId) {
            $details = is_array($d->details ?? null) ? $d->details : [];
            $details['inspection_id'] = $incomingInspectionId;
            $d->details = $details;
        }

        if (!empty($draft['last_step'])) {
            $d->last_step = $draft['last_step'];
        }

        $d->save();

        // pointer only
        $request->session()->put(self::SESSION_KEY, ['draft_uuid' => $d->draft_uuid]);
    }


    private function isModal(Request $request): bool
    {
        return $request->ajax() || $request->expectsJson() || $request->boolean('modal');
    }

    /**
     * Carry wizard context safely across GET/POST/AJAX:
     * - prefer querystring (?customer_id=, ?vehicle_id=, ?modal=1)
     * - then request input
     * - then session draft
     */
    private function carryContext(Request $request): array
    {
        $draft = $this->draft($request);

        $ctx = [
            'customer_id' => $request->query('customer_id') ?? $request->input('customer_id') ?? ($draft['customer_id'] ?? null),
            'vehicle_id'  => $request->query('vehicle_id')  ?? $request->input('vehicle_id')  ?? ($draft['vehicle_id']  ?? null),
            'modal'       => $request->query('modal')       ?? $request->input('modal')       ?? ($draft['modal']       ?? null),
        ];

        return array_filter($ctx, fn ($v) => $v !== null && $v !== '');
    }

    /**
     * Persist context into session draft (so we don’t rely on querystring staying alive)
     */
    private function persistContext(Request $request): void
    {
        $draft = $this->draft($request);
        $ctx   = $this->carryContext($request);

        if (array_key_exists('customer_id', $ctx)) $draft['customer_id'] = $ctx['customer_id'];
        if (array_key_exists('vehicle_id',  $ctx)) $draft['vehicle_id']  = (int) $ctx['vehicle_id'];
        if (array_key_exists('modal',       $ctx)) $draft['modal']       = $ctx['modal'];

        $this->putDraft($request, $draft);
    }

    public function resume(JobDraftService $drafts)
    {
        $draft = $drafts->currentOrCreate(request());

        // Hard resume
        if ($draft->last_step) {
            return redirect()->route(
                'jobs.create.' . $draft->last_step,
                ['draft' => $draft->draft_uuid]
            );
        }

        // Smart fallback
        if (!$draft->payer_type) {
            return redirect()->route('jobs.create.step1', [
                'draft' => $draft->draft_uuid,
            ]);
        }

        if ($draft->payer_type !== 'individual' && empty($draft->payer)) {
            return redirect()->route('jobs.create.step2', [
                'draft' => $draft->draft_uuid,
            ]);
        }

        return redirect()->route('jobs.create.step3', [
            'draft' => $draft->draft_uuid,
        ]);
    }

    public function attachDraftMedia(Request $request)
    {
        $data = $request->validate([
            'draft'        => ['required', 'uuid'],
            'media_item_id'=> ['required', 'integer'],
        ]);

        $draft = JobDraft::where('garage_id', app('garage')->id ?? auth()->user()->garage_id)
            ->where('draft_uuid', $data['draft'])
            ->where('status', 'draft')
            ->firstOrFail();

        // Prevent duplicates
        $exists = MediaAttachment::where('attachable_type', JobDraft::class)
            ->where('attachable_id', $draft->id)
            ->where('media_item_id', $data['media_item_id'])
            ->exists();

        if (!$exists) {
            MediaAttachment::create([
                'garage_id'       => $draft->garage_id,
                'media_item_id'   => $data['media_item_id'],
                'attachable_type' => JobDraft::class,
                'attachable_id'   => $draft->id,
            ]);
        }

        // Return latest photos so UI can refresh without reload
        $draft->load(['mediaAttachments.mediaItem']);

        return response()->json([
            'ok' => true,
            'photos' => $draft->mediaAttachments->map(function ($a) {
                $m = $a->mediaItem;
                return [
                    'attachment_id' => $a->id,
                    'media_item_id' => $m->id ?? null,
                    'url'           => $m->url ?? $m->public_url ?? null, // adjust to your column
                    'name'          => $m->original_name ?? $m->filename ?? null,
                ];
            })->values(),
        ]);
    }


    /**
     * Modal flow: return JSON { next_url } for JS to fetch next step.
     * Non-modal: redirect to next url.
     */
    private function modalNext(Request $request, string $url): JsonResponse|RedirectResponse
    {
        if ($this->isModal($request)) {
            return response()->json(['next_url' => $url]);
        }

        return redirect()->to($url);
    }

    /*
    |--------------------------------------------------------------------------
    | STEP 1 (Type)
    |--------------------------------------------------------------------------
    */
    public function step1(Request $request): View
    {
        $this->persistContext($request);

        // If user is opening Step 1, treat as a new wizard session unless explicitly continuing
        if (!$request->boolean('resume')) {
            $this->resetDraft($request, true);
        }

        $draft   = $this->draft($request);
        $isModal = $this->isModal($request);

        $view = $isModal ? 'jobs.create.partials.step-1' : 'jobs.create.step-1';

        return view($view, [
            'payer_type' => $draft['payer_type'],
            'modal'      => $isModal,
        ]);
    }

    public function postStep1(Request $request, \App\Services\JobDraftService $drafts): JsonResponse|RedirectResponse
    {
        $this->persistContext($request);

        $data = $request->validate([
            'payer_type' => ['required', 'in:individual,company,insurance'],
        ]);

        // ✅ Load draft by UUID if present, otherwise currentOrCreate
        $draft = $request->filled('draft')
            ? $drafts->loadOrFail($request, (string) $request->input('draft'))
            : $drafts->currentOrCreate($request);

        $payerType = $data['payer_type'];

        // ✅ Persist payer type (and wipe payer json if type changed)
        $drafts->setPayerType($draft, $payerType);

        // ✅ Track resume pointer
        $drafts->touchStep($draft, 'step1');

        // ✅ Keep your existing context logic
        $ctx = $this->carryContext($request);

        // ✅ Always carry draft UUID forward
        $pageCtx = $ctx;
        unset($pageCtx['modal']);
        $pageCtx['draft'] = $draft->draft_uuid;

        /*
        -------------------------------------------------
        INDIVIDUAL → SKIP STEP 2 → GO TO STEP 3 (PAGE)
        -------------------------------------------------
        */
        if ($payerType === 'individual') {

            $pageUrl = route('jobs.create.step3', $pageCtx);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'ok'         => true,
                    'payer_type' => $payerType,
                    'next_url'   => $pageUrl,
                ]);
            }

            return redirect()->to($pageUrl);
        }

        /*
        -------------------------------------------------
        COMPANY / INSURANCE → STEP 2 (PAGE)
        -------------------------------------------------
        */
        if (in_array($payerType, ['company', 'insurance'], true)) {

            $pageUrl = route('jobs.create.step2', $pageCtx);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'ok'         => true,
                    'payer_type' => $payerType,
                    'next_url'   => $pageUrl,
                ]);
            }

            return redirect()->to($pageUrl);
        }

        return response()->json([
            'ok' => false,
            'message' => 'Invalid payer type',
        ], 400);
    }




    /*
    |--------------------------------------------------------------------------
    | STEP 2 (Payer details)
    |--------------------------------------------------------------------------
    */
    public function step2(Request $request): View|RedirectResponse
    {
        // Keep modal/customer/vehicle context stable across wizard steps
        $this->persistContext($request);

        $draft = $this->draft($request);

        // ✅ Ensure draft job exists early for company/insurance once vehicle is known
        if (!empty($draft['payer_type']) && empty($draft['job_id']) && !empty($draft['vehicle_id'])) {
            $this->ensureDraftJob($request, $draft);
            $draft = $this->draft($request); // reload after creating job
        }

        // Step 1 required
        if (empty($draft['payer_type'])) {
            return redirect()->route('jobs.create.step1', $this->carryContext($request));
        }

        // Individual skips payer step
        if ($draft['payer_type'] === 'individual') {
            return redirect()->route('jobs.create.step3', $this->carryContext($request));
        }

        $garageId = auth()->user()->garage_id;

        // Load only what we need for the selected payer type (fast + clean)
        $organizations = [];
        $insurers      = [];

        if ($draft['payer_type'] === 'company') {
            $orgQuery = \App\Models\Organization::query()->orderBy('name');

            // ✅ schema-safe: only filter if column exists
            try {
                if (\Schema::hasColumn('organizations', 'garage_id')) {
                    $orgQuery->where('garage_id', $garageId);
                }
            } catch (\Throwable $e) {
                // ignore schema inspection failures; fall back to global list
            }

            $organizations = $orgQuery->get(['id', 'name']);
        }

        if ($draft['payer_type'] === 'insurance') {
            $insQuery = \App\Models\Insurer::query()->orderBy('name');

            // ✅ schema-safe filters (avoid breaking if columns don’t exist yet)
            try {
                if (\Schema::hasColumn('insurers', 'garage_id')) {
                    $insQuery->where('garage_id', $garageId);
                }
                if (\Schema::hasColumn('insurers', 'is_active')) {
                    $insQuery->where('is_active', true);
                }
            } catch (\Throwable $e) {
                // ignore schema inspection failures; fall back to global list
            }

            $insurers = $insQuery->get(['id', 'name']);
        }

        // Modal vs full page rendering
        $isModal = $this->isModal($request);

        if ($isModal) {
            $view = 'jobs.create.partials.step-2';
        } else {
            // ✅ Use existing wrapper views (full layout)
            if ($draft['payer_type'] === 'insurance') {
                $view = 'jobs.insurance.step-2';   // ✅ existing file
            } else {
                $view = 'jobs.company.step-2';     // ✅ create this (or switch to your existing generic wrapper)
            }
        }


        return view($view, [
            'payer_type'    => $draft['payer_type'],
            'payer'         => $draft['payer'] ?? [],
            'organizations' => $organizations,
            'insurers'      => $insurers,
            'modal'         => $isModal,
        ]);
    }
    
    public function postStep2(Request $request, \App\Services\JobDraftService $drafts): JsonResponse|RedirectResponse
    {
        $this->persistContext($request);

        \Log::info('POST STEP2 DRAFT RESOLVE', [
            'draft_input'   => $request->input('draft'),
            'draft_query'   => $request->query('draft'),
            'payer_type_in' => $request->input('payer_type'),
        ]);

        \Log::info('STEP2 RESPONSE MODE', [
            'ajax'         => $request->ajax(),
            'expectsJson'  => $request->expectsJson(),
            'accept'       => $request->header('Accept'),
            'xrw'          => $request->header('X-Requested-With'),
            'content_type' => $request->header('Content-Type'),
        ]);

        // Resolve draft UUID from POST first, then querystring, then session fallback
        $draftUuid = (string) ($request->input('draft') ?? $request->query('draft') ?? '');

        $draftModel = $draftUuid !== ''
            ? $drafts->loadOrFail($request, $draftUuid)
            : $drafts->currentOrCreate($request);

        // ✅ PATCH: recover payer_type from POST if draft is missing it
        if (empty($draftModel->payer_type) && $request->filled('payer_type')) {
            $draftModel->payer_type = (string) $request->input('payer_type');
            $draftModel->save();
        }

        // ✅ PATCH: only bounce if payer_type missing everywhere (draft + POST)
        if (empty($draftModel->payer_type) && empty($request->input('payer_type'))) {
            $url = route('jobs.create.step1', $this->carryContext($request));

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json(['ok' => false, 'next_url' => $url], 422);
            }

            return redirect()->to($url);
        }

        try {

            /*
            |----------------------------------------------------------
            | INDIVIDUAL → Save minimal, create job, go to Job Edit
            |----------------------------------------------------------
            */
            if ($draftModel->payer_type === 'individual') {

                // advance wizard pointer centrally
                $drafts->touchStep($draftModel, 'step2');

                // ensure job exists
                $this->ensureDraftJob($request, $draftModel->toArray());

                $garageId = auth()->user()->garage_id;

                $jobId = (int) \App\Models\JobDraft::query()
                    ->where('garage_id', $garageId)
                    ->where('draft_uuid', $draftModel->draft_uuid)
                    ->value('job_id');

                if ($jobId > 0) {
                    $pageUrl = route('jobs.edit', ['job' => $jobId]);

                    if ($request->ajax() || $request->expectsJson()) {
                        return response()->json(['ok' => true, 'next_url' => $pageUrl]);
                    }

                    return redirect()->to($pageUrl);
                }

                // fallback
                $url = route('jobs.create.step1', $this->carryContext($request));

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['ok' => false, 'next_url' => $url], 422);
                }

                return redirect()->to($url);
            }

            /*
            |----------------------------------------------------------
            | COMPANY → Save payer details, create job, go to Job Edit
            |----------------------------------------------------------
            */
            if ($draftModel->payer_type === 'company') {

                $data = $request->validate([
                    'organization_id' => ['required', 'integer'],
                    'po_ref'          => ['nullable', 'string', 'max:100'],
                    'billing_notes'   => ['nullable', 'string', 'max:500'],
                ]);

                $draftModel->payer = [
                    'organization_id' => (int) $data['organization_id'],
                    'po_ref'          => $data['po_ref'] ?? null,
                    'billing_notes'   => $data['billing_notes'] ?? null,
                ];

                $draftModel->save();

                // advance wizard pointer centrally
                $drafts->touchStep($draftModel, 'step2');

                // ensure job exists
                $this->ensureDraftJob($request, $draftModel->toArray());

                $garageId = auth()->user()->garage_id;

                $jobId = (int) \App\Models\JobDraft::query()
                    ->where('garage_id', $garageId)
                    ->where('draft_uuid', $draftModel->draft_uuid)
                    ->value('job_id');

                if ($jobId > 0) {
                    $pageUrl = route('jobs.edit', ['job' => $jobId]);

                    if ($request->ajax() || $request->expectsJson()) {
                        return response()->json(['ok' => true, 'next_url' => $pageUrl]);
                    }

                    return redirect()->to($pageUrl);
                }

                // fallback
                $url = route('jobs.create.step2', array_merge($this->carryContext($request), [
                    'draft' => $draftModel->draft_uuid,
                ]));

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['ok' => false, 'next_url' => $url], 422);
                }

                return redirect()->to($url)->withErrors(['error' => 'Job was not created. Please try again.']);
            }

            /*
            |----------------------------------------------------------
            | INSURANCE → Save payer details, create job, go to Insurance Show
            |----------------------------------------------------------
            */
            if ($draftModel->payer_type === 'insurance') {

                $data = $request->validate([
                    'insurer_id'     => ['required', 'integer'],
                    'policy_no'      => ['required', 'string', 'max:100'],
                    'claim_no'       => ['required', 'string', 'max:100'],
                    'lpo_number'     => ['nullable', 'string', 'max:100'],

                    'excess_amount'  => ['nullable', 'string', 'max:50'],
                    'adjuster'       => ['nullable', 'string', 'max:120'],
                    'adjuster_phone' => ['nullable', 'string', 'max:50'],
                    'notes'          => ['nullable', 'string', 'max:2000'],
                ]);

                \Log::info('STEP2 INSURANCE PAYLOAD', [
                    'draft_param' => $request->input('draft'),
                    'draft_db'    => $draftModel->draft_uuid,
                    'job_id_db'   => $draftModel->job_id,
                    'garage_id'   => auth()->user()->garage_id,
                    'insurer_id'  => $request->input('insurer_id'),
                    'policy_no'   => $request->input('policy_no'),
                    'claim_no'    => $request->input('claim_no'),
                ]);

                $garageId = auth()->user()->garage_id;

                // garage-scoped insurer guard
                $insQuery = \App\Models\Insurer::query()
                    ->where('id', (int) $data['insurer_id']);

                try {
                    if (\Schema::hasColumn('insurers', 'garage_id')) {
                        $insQuery->where('garage_id', $garageId);
                    }
                    if (\Schema::hasColumn('insurers', 'is_active')) {
                        $insQuery->where('is_active', true);
                    }
                } catch (\Throwable $e) {
                    // fallback silently
                }

                $insurer = $insQuery->first(['id', 'name']);

                if (!$insurer) {
                    $payload = [
                        'ok'      => false,
                        'message' => 'Selected insurer not found for this garage.',
                        'errors'  => ['insurer_id' => ['Selected insurer not found for this garage.']],
                    ];

                    if ($request->ajax() || $request->expectsJson()) {
                        return response()->json($payload, 422);
                    }

                    return back()->withErrors(['insurer_id' => $payload['message']])->withInput();
                }

                // canonical insurance payload
                $draftModel->payer = [
                    'insurance' => [
                        'insurer_id'     => (int) $insurer->id,
                        'insurer_name'   => $insurer->name,
                        'policy_number'  => trim($data['policy_no']),
                        'claim_number'   => trim($data['claim_no']),
                        'lpo_number'     => isset($data['lpo_number']) && trim($data['lpo_number']) !== '' ? trim($data['lpo_number']) : null,
                        'excess_amount'  => $data['excess_amount'] ?? null,
                        'adjuster_name'  => $data['adjuster'] ?? null,
                        'adjuster_phone' => $data['adjuster_phone'] ?? null,
                        'notes'          => $data['notes'] ?? null,
                    ],
                ];

                $draftModel->save();

                // advance wizard pointer centrally
                $drafts->touchStep($draftModel, 'step2');

                // ensure job exists
                $this->ensureDraftJob($request, $draftModel->toArray());

                // reload job_id from DB (garage-scoped)
                $jobId = (int) \App\Models\JobDraft::query()
                    ->where('garage_id', $garageId)
                    ->where('draft_uuid', $draftModel->draft_uuid)
                    ->value('job_id');

                \Log::info('STEP2 INSURANCE jobId check', [
                    'draft_uuid' => $draftModel->draft_uuid,
                    'jobId'      => $jobId,
                    'garage_id'  => $garageId,
                ]);

                if ($jobId > 0) {
                    // persist insurance details table (canonical)
                    \App\Models\JobInsuranceDetail::updateOrCreate(
                        ['job_id' => $jobId],
                        [
                            'garage_id'      => $garageId,
                            'insurer_id'     => (int) $insurer->id,
                            'insurer_name'   => $insurer->name,
                            'policy_number'  => trim($data['policy_no']),
                            'claim_number'   => trim($data['claim_no']),
                            'lpo_number'     => isset($data['lpo_number']) && trim($data['lpo_number']) !== '' ? trim($data['lpo_number']) : null,
                            'excess_amount'  => isset($data['excess_amount']) && trim($data['excess_amount']) !== '' ? (float) $data['excess_amount'] : null,
                            'adjuster_name'  => $data['adjuster'] ?? null,
                            'adjuster_phone' => $data['adjuster_phone'] ?? null,
                            'notes'          => $data['notes'] ?? null,
                        ]
                    );

                    // ✅ One path: continue at Insurance Show
                    $pageUrl = route('jobs.insurance.show', ['job' => $jobId]);

                    if ($request->ajax() || $request->expectsJson()) {
                        return response()->json(['ok' => true, 'next_url' => $pageUrl]);
                    }

                    \Log::info('STEP2 REDIRECT TO (CANONICAL)', [
                        'payer_type' => $draftModel->payer_type,
                        'draft'      => $draftModel->draft_uuid,
                        'job_id'     => $jobId,
                        'to'         => $pageUrl,
                    ]);

                    return redirect()->to($pageUrl);
                }

                // fallback if job wasn't created
                $pageUrl = route('jobs.create.step2', array_merge($this->carryContext($request), [
                    'draft' => $draftModel->draft_uuid,
                ]));

                if ($request->ajax() || $request->expectsJson()) {
                    return response()->json(['ok' => false, 'next_url' => $pageUrl], 422);
                }

                return redirect()->to($pageUrl)->withErrors(['error' => 'Job was not created. Please try again.']);
            }

            // Unknown payer type fallback
            return redirect()->route('jobs.create.step1', $this->carryContext($request));

        } catch (\Throwable $e) {
            report($e);

            if ($request->ajax() || $request->expectsJson()) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Failed to save payer details.',
                ], 500);
            }

            return back()->withErrors(['error' => 'Failed to save payer details.'])->withInput();
        }
    }



    /*
    |--------------------------------------------------------------------------
    | STEP 3 (Job)  — FULL PATCHED DROP-IN
    |--------------------------------------------------------------------------
    | ✅ Fixes endless "new draft inspections" creation
    | ✅ Makes job_drafts.details.inspection_id the SINGLE source of truth
    | ✅ Validates inspection belongs to same garage
    | ✅ Persists inspection_id using putDraft() (and re-reads fresh)
    | ✅ Loads attachments for immediate photo rendering
    | ✅ Leaves the rest of your flow intact
    */
    public function step3(Request $request): View|RedirectResponse
    {
        // Keep modal/customer/vehicle context stable across wizard steps
        $this->persistContext($request);

        $draft = $this->draft($request);

        // ✅ Ensure a draft job exists before working in Step 3 (all flows)
        if (!empty($draft['payer_type']) && empty($draft['job_id'])) {
            $this->ensureDraftJob($request, $draft);
            $draft = $this->draft($request); // reload after creation
        }

        // ✅ Recover vehicle_id into draft if it was passed via query/input but not persisted
        if (empty($draft['vehicle_id'])) {
            $ctx = $this->carryContext($request);

            if (!empty($ctx['vehicle_id'])) {
                $draft['vehicle_id'] = (int) $ctx['vehicle_id'];
                $this->putDraft($request, $draft);
                $draft = $this->draft($request);

                // Ensure job exists after vehicle recovery
                if (empty($draft['job_id']) && !empty($draft['payer_type'])) {
                    $this->ensureDraftJob($request, $draft);
                    $draft = $this->draft($request);
                }
            }
        }

        if (empty($draft['payer_type'])) {
            return redirect()->route('jobs.create.step1', $this->carryContext($request));
        }

        $payerType = $draft['payer_type'];

        // ✅ payerReady must validate the correct payer shape per type
        $payerReady = match ($payerType) {
            'individual' => true,
            'company'    => !empty(($draft['payer']['organization_id'] ?? null)),
            'insurance'  => !empty(($draft['payer']['insurance']['insurer_id'] ?? null)),
            default      => false,
        };

        if (in_array($payerType, ['company', 'insurance'], true) && !$payerReady) {
            return redirect()->route('jobs.create.step2', $this->carryContext($request));
        }

        if (empty($draft['vehicle_id'])) {
            return redirect()
                ->route('jobs.create.step1', $this->carryContext($request))
                ->withErrors(['vehicle' => 'No vehicle selected for this job. Please select a vehicle first.']);
        }

        // ✅ only merge if missing (avoid polluting request)
        if (!$request->filled('vehicle_id') && !empty($draft['vehicle_id'])) {
            $request->merge(['vehicle_id' => $draft['vehicle_id']]);
        }
        if (!$request->filled('customer_id') && !empty($draft['customer_id'])) {
            $request->merge(['customer_id' => $draft['customer_id']]);
        }

        // ✅ prefill basics from draft (supports your Alpine gating)
        if (!empty($draft['details'])) {
            $request->merge([
                'job_date'     => $request->input('job_date')     ?? ($draft['details']['job_date'] ?? null),
                'mileage'      => $request->input('mileage')      ?? ($draft['details']['mileage'] ?? null),
                'service_type' => $request->input('service_type') ?? ($draft['details']['service_type'] ?? null),
            ]);
        }

        // Build your canonical payload using existing builder
        $payload = app(\App\Http\Controllers\JobController::class)->buildCreatePayload($request);

        $payload['payer_type']  = $payerType;
        $payload['payer']       = $draft['payer'] ?? [];
        $payload['vehicle_id']  = $draft['vehicle_id'];
        $payload['customer_id'] = $draft['customer_id'] ?? null;
        $payload['payer_ready'] = $payerReady;

        // ✅ unlock state: DB truth (draft details) OR old() fallback
        $payload['unlocked_job_rest'] = (bool) old(
            'unlocked_job_rest',
            $draft['details']['unlocked_job_rest'] ?? false
        );

        $payload['should_unlock_rest_default'] = false;
        $payload['modal'] = $this->isModal($request);

        /*
        |--------------------------------------------------------------------------
        | ✅ INSURANCE: SINGLE SOURCE OF TRUTH = job_drafts.details.inspection_id
        |--------------------------------------------------------------------------
        */
        if ($payerType === 'insurance') {

            // Ensure we have a draft_uuid (root-level)
            $this->ensureDraftUuid($request, $draft);
            $draft = $this->draft($request); // re-read (ensure it’s persisted)

            $garageId = (int) ($draft['garage_id'] ?? auth()->user()?->garage_id ?? 0);

            // 1) Load inspection via draft pointer
            $inspection = null;
            $inspectionId = (int) data_get($draft, 'details.inspection_id', 0);

            if ($inspectionId > 0) {
                $inspection = \App\Models\JobInspection::query()
                    ->where('id', $inspectionId)
                    ->where('garage_id', $garageId)
                    ->first();
            }

            // 2) Create exactly ONE if missing, then persist pointer to draft.details
            if (!$inspection) {
                $inspection = \App\Models\JobInspection::create([
                    'garage_id' => $garageId,
                    'job_id'    => null,      // wizard stage
                    'status'    => 'draft',
                ]);

                $draft['details'] = array_merge($draft['details'] ?? [], [
                    'inspection_id' => (int) $inspection->id,
                ]);

                $this->putDraft($request, $draft);
                $draft = $this->draft($request); // ✅ re-read after persist
            }

            // 3) Load attachments so photos render immediately
            $inspection->load(['mediaAttachments.mediaItem']);

            $payload['inspection'] = $inspection;
            $payload['draft']      = $draft;

            // Photos array expected by your blade/alpine
            $payload['inspection_photos'] = $inspection->mediaAttachments
                ->filter(fn ($a) => $a->mediaItem)
                ->map(function ($a) {
                    $m = $a->mediaItem;

                    $url = $m->public_url
                        ?? $m->url
                        ?? $m->src
                        ?? null;

                    return [
                        'attachment_id' => $a->id,
                        'media_item_id' => $m->id,
                        'url'           => $url,
                        'thumb_url'     => $m->thumb_url ?? $m->public_thumb_url ?? null,
                        'name'          => $m->original_name ?? $m->filename ?? null,
                    ];
                })
                ->values()
                ->all();

            // Blade mount expects these
            $payload['minPhotos']   = 4;
            $payload['totalItems']  = 58;
            $payload['attached']    = $payload['inspection_photos'] ?? [];
            $payload['photosCount'] = count($payload['attached']);

            // Lock driven purely by DB truth
            $payload['locked'] = (($inspection->status ?? null) === 'completed');

            // Checklist done count (items with a chosen state)
            $payload['checklistDone'] = \App\Models\JobInspectionItem::query()
                ->where('inspection_id', $inspection->id)
                ->whereNotNull('state')
                ->count();
        }

        // ✅ Use the standard full-page view for Step 3 (insurance included)
        $view = $this->isModal($request) ? 'jobs.create.partials.step-3' : 'jobs.create.step-3';

        return view($view, $payload);
    }



    public function postStep3(Request $request): JsonResponse|RedirectResponse
    {
        $this->persistContext($request);

        $request->validate([
            'job_date'     => ['required', 'date'],
            'mileage'      => ['required', 'numeric', 'min:0'],
            'service_type' => ['required', 'string', 'max:255'],
        ]);

        // Build context
        $ctx = $this->carryContext($request);

        // 🔥 IMPORTANT: Strip modal when going to Step 4
        unset($ctx['modal']);

        // Save Step 3 data into draft
        $draft = $this->draft($request);

        $draft['details'] = array_merge($draft['details'] ?? [], [
            'job_date'          => $request->input('job_date'),
            'mileage'           => $request->input('mileage'),
            'service_type'      => $request->input('service_type'),
            'unlocked_job_rest' => (bool) $request->input('unlocked_job_rest', false),
        ]);

        $this->putDraft($request, $draft);

        // ✅ Insurance: jump straight into Insurance Workspace (same UI as Jobs > Edit)
        $draft = $this->draft($request);
        
        if (($draft['payer_type'] ?? null) === 'insurance' && !empty($draft['job_id'])) {
            return $this->modalNext(
                $request,
                route('jobs.insurance.show', ['job' => (int) $draft['job_id']])
            );
        }

        // Default: continue existing wizard finish/review
        return $this->modalNext(
            $request,
            route('jobs.create.step4', $ctx)
        );

    }

    /*
    |--------------------------------------------------------------------------
    | STEP 4 (Review / Success)
    |--------------------------------------------------------------------------
    */
    public function step4(Request $request): View
    {
        $this->persistContext($request);

        $isModal = $this->isModal($request);
        $view    = $isModal ? 'jobs.create.partials.step-4' : 'jobs.create.step-4';

        $draft = $this->draft($request);

        // If you later create the job on Step 4 confirm, you'll set last_job_id.
        $job = null;
        if (!empty($draft['last_job_id'])) {
            $job = Job::where('garage_id', auth()->user()->garage_id)
                ->find($draft['last_job_id']);
        }

        return view($view, [
            'modal'   => $isModal,
            'ctx'     => $this->carryContext($request),
            'job'     => $job,
            'draft'   => $draft,                  // ✅ lets Step 4 display review data
            'details' => $draft['details'] ?? [], // ✅ easy access in blade
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | CONFIRM (Finalize Job)
    |--------------------------------------------------------------------------
    | ✅ Finalizes the EXISTING draft job (no duplicates)
    | ✅ Falls back to legacy store if no draft job exists (safe)
    | ✅ Persists job_id + last_job_id into JobDraft for Step 4 + resume
    |
    | IMPORTANT prereqs elsewhere in controller:
    | 1) draft() must expose 'job_id' (and optionally keep last_job_id alias)
    | 2) putDraft() must persist 'job_id' to JobDraft row:
    |    if (array_key_exists('job_id', $draft)) $d->job_id = $draft['job_id'];
    | 3) step3() should ensure a draft job exists before confirm:
    |    if (!empty($draft['payer_type']) && empty($draft['job_id'])) $this->ensureDraftJob(...)
    */
    public function confirm(Request $request): JsonResponse|RedirectResponse
    {
        $this->persistContext($request);

        $draft = $this->draft($request);
        $ctx   = $this->carryContext($request);

        // ✅ Must have Step 3 basics saved in draft
        $details = $draft['details'] ?? [];
        if (
            empty($details['job_date']) ||
            ($details['mileage'] ?? '') === '' ||
            empty($details['service_type'])
        ) {
            return $this->modalNext($request, route('jobs.create.step3', $ctx));
        }

        // ✅ Hard guards
        if (empty($draft['payer_type']) || empty($draft['vehicle_id'])) {
            return $this->modalNext($request, route('jobs.create.step1', $ctx));
        }

        // ------------------------------------------------------------
        // Build a "store-like" payload into the request
        // ------------------------------------------------------------
        $request->merge([
            'payer_type'   => $draft['payer_type'],
            'customer_id'  => $draft['customer_id'] ?? null,
            'vehicle_id'   => $draft['vehicle_id'],

            'job_date'     => $details['job_date'],
            'mileage'      => $details['mileage'],
            'service_type' => $details['service_type'],

            // Optional common fields
            'labour_cost'  => $details['labour_cost'] ?? $request->input('labour_cost'),
            'part_items'   => $details['part_items']  ?? $request->input('part_items', []),
            'notes'        => $details['notes']       ?? $request->input('notes'),
        ]);

        // ------------------------------------------------------------
        // Map payer → what legacy store expects (kept for payload shape)
        // ------------------------------------------------------------
        if ($draft['payer_type'] === 'company') {
            $request->merge([
                'organization_id' => $draft['payer']['organization_id'] ?? null,
            ]);
        }

        if ($draft['payer_type'] === 'insurance') {
            $insurance = $draft['payer']['insurance'] ?? [];

            $request->merge([
                'organization_id' => $insurance['insurer_id'] ?? null,
                'insurance' => [
                    'policy_number'  => $insurance['policy_number'] ?? null,
                    'claim_number'   => $insurance['claim_number'] ?? null,
                    'lpo_number'     => $insurance['lpo_number'] ?? null,
                    'adjuster_name'  => $insurance['adjuster_name'] ?? null,

                    'excess_amount'  => $insurance['excess_amount'] ?? null,
                    'adjuster_phone' => $insurance['adjuster_phone'] ?? null,
                    'notes'          => $insurance['notes'] ?? null,
                ],
            ]);
        }

        // ------------------------------------------------------------
        // ✅ Preferred: FINALIZE existing draft job (no duplicates)
        // ------------------------------------------------------------
        $job = null;

        $jobId = (int) ($draft['job_id'] ?? 0);
        if ($jobId > 0) {

            $garageId = auth()->user()->garage_id;

            $job = Job::query()
                ->where('garage_id', $garageId)
                ->find($jobId);

            if (!$job) {
                // if pointer is stale, clear and fallback
                $draft['job_id'] = null;
                $draft['last_job_id'] = null;
                $this->putDraft($request, $draft);
                $jobId = 0;
            } else {

                // Build canonical payload using your existing builder
                $jobController = app(\App\Http\Controllers\JobController::class);
                $payload = $jobController->buildCreatePayload($request);

                DB::transaction(function () use ($job, $payload, $jobController) {

                    // Assign job number ONLY on finalize (drafts keep NULL)
                    if (empty($job->job_number)) {
                        $job->job_number = $jobController->generateJobNumber($job->garage_id);
                    }

                    // Finalize fields (same intent as store())
                    $job->fill([
                        'job_date'       => $payload['job_date'] ?? $job->job_date,
                        'service_type'   => $payload['service_type'] ?? $job->service_type,
                        'complaint'      => $payload['complaint'] ?? $job->complaint,
                        'diagnosis'      => $payload['diagnosis'] ?? $job->diagnosis,
                        'work_done'      => $payload['work_done'] ?? $job->work_done,
                        'parts_used'     => $payload['parts_used'] ?? $job->parts_used,
                        'mileage'        => $payload['mileage'] ?? $job->mileage,

                        'labour_cost'    => $payload['labour_cost'] ?? $job->labour_cost,
                        'parts_cost'     => $payload['parts_cost'] ?? $job->parts_cost,
                        'estimated_cost' => $payload['estimated_cost'] ?? $job->estimated_cost,
                        'final_cost'     => $payload['final_cost'] ?? $job->final_cost,

                        'notes'          => $payload['notes'] ?? $job->notes,

                        // ✅ Finalize status (pick your desired final state)
                        'status'         => $payload['status'] ?? 'pending',
                    ]);

                    // Enforce payer context on finalize too
                    if (isset($payload['payer_type'])) {
                        $job->payer_type = $payload['payer_type'];
                    }
                    if (array_key_exists('organization_id', $payload)) {
                        $job->organization_id = $payload['organization_id'];
                    }

                    $job->save();

                    // Insurance details sync (uses your existing canonical method)
                    if (($job->payer_type ?? null) === 'insurance' && isset($payload['insurance'])) {
                        $jobController->syncInsuranceDetailsForJob($job->id, $job->payer_type, $payload['insurance'] ?? null);
                    }

                    // Part items sync
                    if (isset($payload['part_items']) && is_array($payload['part_items'])) {
                        $jobController->syncJobPartItems($job, $payload['part_items']);
                    }
                });

                // refresh after transaction
                $job->refresh();
            }
        }

        // ------------------------------------------------------------
        // Fallback: legacy store (safe if job_id missing)
        // ------------------------------------------------------------
        if (!$job) {

            $jobController = app(\App\Http\Controllers\JobController::class);
            $response = $jobController->store($request);

            // If legacy controller returned a response, swallow it in modal mode
            if ($request->expectsJson() || $request->ajax()) {
                // do nothing
            } elseif ($response instanceof \Illuminate\Http\Response || $response instanceof \Illuminate\Http\RedirectResponse) {
                return $response;
            }

            // Resolve the created Job
            $job = Job::where('garage_id', auth()->user()->garage_id)
                ->where('created_by', auth()->id())
                ->latest('id')
                ->first();
        }

        // ------------------------------------------------------------
        // Persist job pointer into draft for Step 4 lock / resume
        // ------------------------------------------------------------
        if ($job) {
            $draft['job_id']      = $job->id;
            $draft['last_job_id'] = $job->id; // keep compatibility with your Step4
            $this->putDraft($request, $draft);
        }

        // ✅ Insurance goes straight into the Insurance Workspace (full page)
        if (($draft['payer_type'] ?? null) === 'insurance' && $job) {
            return $this->modalNext($request, route('jobs.insurance.show', ['job' => $job->id]));
        }

        // Default: continue existing wizard finish/review
        return $this->modalNext($request, route('jobs.create.step4', $ctx));
    }


    /*
    |--------------------------------------------------------------------------
    | INSURANCE INSPECTION (STEP 3 MODULE)
    |--------------------------------------------------------------------------
    | These endpoints are called from the Step 3 insurance inspection partial.
    */
    public function inspectionSave(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') abort(403);

        $this->ensureDraftUuid($request, $draft);

        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return $this->jsonOrBack($request, false, 'Inspection not available.');
        }

        if ($inspection->status === 'completed') {
            return $this->jsonOrBack($request, false, 'Inspection is completed and locked.');
        }

        // Expect payload: items[item_no][state], items[item_no][notes]
        $data = $request->validate([
            'items'                 => ['required', 'array'],
            'items.*.state'         => ['nullable', 'in:ok,damaged,missing'],
            'items.*.notes'         => ['nullable', 'string', 'max:500'],
        ]);

        $itemsInput = $data['items'] ?? [];

        // Update only what was sent (avoid heavy loops if you later paginate sections)
        foreach ($itemsInput as $itemNo => $row) {
            $itemNo = (int) $itemNo;

            $state = $row['state'] ?? null;
            $notes = $row['notes'] ?? null;

            // Only update existing seeded rows
            \App\Models\JobInspectionItem::query()
                ->where('garage_id', auth()->user()->garage_id)
                ->where('inspection_id', $inspection->id)
                ->where('item_no', $itemNo)
                ->update([
                    'state' => in_array($state, ['ok','damaged','missing'], true) ? $state : null,
                    'notes' => (is_string($notes) && trim($notes) !== '') ? trim($notes) : null,
                    'updated_at' => now(),
                ]);
        }

        // Optional: compute counts to help UI
        $counts = $this->inspectionCounts($inspection->id);

        return $this->jsonOrBack($request, true, 'Inspection saved.', array_merge($counts, [
            'saved_at' => now()->format('Y-m-d H:i'),
        ]));
    }


    public function inspectionPhoto(Request $request): RedirectResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') abort(403);

        $request->validate([
            'photo' => ['required', 'image', 'max:5120'], // 5MB
        ]);

        // Ensure inspection exists
        $this->ensureDraftUuid($request, $draft);

        $inspection = $this->ensureInsuranceInspection($request, $draft);


        if (!$inspection) {
            return back()->with('error', 'Inspection not available.');
        }

        if ($inspection->status === 'completed') {
            return back()->with('error', 'Inspection is completed and locked; cannot add photos.');
        }

        /**
         * TODO: Wire to your Vault-lite implementation:
         * - create MediaItem for $request->file('photo')
         * - attach to $inspection via MediaAttachment morph
         * - set label='inspection_photo'
         *
         * Keep this controller method stable; we’ll drop in your actual media service call next.
         */

        return back()->with('success', 'Photo uploaded (hook pending).');
    }

    public function inspectionChecklistLoad(Request $request): \Illuminate\Http\JsonResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') abort(403);

        $this->ensureDraftUuid($request, $draft);
        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return response()->json([
                'ok' => false,
                'message' => 'Inspection not available.',
            ], 422);
        }

        $garageId = auth()->user()->garage_id;

        $rows = \App\Models\JobInspectionItem::query()
            ->where('garage_id', $garageId)
            ->where('inspection_id', $inspection->id)
            ->orderBy('item_no')
            ->get(['item_no', 'state', 'notes']);

        // Map to your UI shape: { "1": {status, note}, ... }
        $items = [];
        foreach ($rows as $r) {
            $no = (int) $r->item_no;
            $items[(string)$no] = [
                'status' => $r->state ?: null,
                'note'   => $r->notes ?: '',
            ];
        }

        // "done" = status chosen (ok/damaged/missing)
        $done = 0;
        foreach ($items as $it) {
            if (!empty($it['status'])) $done++;
        }

        // also return counts for photos/progress if you like
        $counts = $this->inspectionCounts($inspection->id);

        return response()->json([
            'ok' => true,
            'inspection_id' => $inspection->id,
            'items' => $items,
            'doneItems' => $done,
            'totalItems' => 58,
            'photosCount' => $counts['photos_count'] ?? 0,
            'changes' => $counts['changes'] ?? 0,
        ]);
    }

    public function inspectionComplete(Request $request): JsonResponse|RedirectResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') {
            abort(403);
        }

        $this->ensureDraftUuid($request, $draft);

        // ✅ Canonical inspection (draft.details.inspection_id is the anchor)
        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return $this->jsonOrBack($request, false, 'Inspection not available.');
        }

        $garageId = auth()->user()->garage_id;

        // ✅ ALWAYS resolve Job ID from multiple sources (wizard often doesn’t send "job")
        $jobId = (int) (
            $request->input('job')
            ?: ($draft['job_id'] ?? 0)
            ?: ($inspection->job_id ?? 0)
        );

        // ✅ If we have a job, ensure inspection is linked to it
        if ($jobId > 0 && empty($inspection->job_id)) {
            $inspection->update(['job_id' => $jobId]);
        }

        // If already completed, still force job gate sync (this is what unlocks quotation)
        if (($inspection->status ?? null) === 'completed') {

            if ($jobId > 0) {
                \App\Models\Job::where('garage_id', $garageId)
                    ->where('id', $jobId)
                    ->update([
                        'inspection_completed_at' => $inspection->completed_at ?? now(),
                        'quotation_unlocked'      => true,
                    ]);
            }

            $counts = $this->inspectionCounts($inspection->id);

            return $this->jsonOrBack(
                $request,
                true,
                'Inspection already completed.',
                array_merge($counts, [
                    'locked'  => true,
                    'job_id'  => $jobId,
                ])
            );
        }

        // ✅ Enforce minimum photos (DB truth: Vault attaches to JOB)
        $photosCount = \App\Models\MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\Job::class)
            ->where('attachable_id', $jobId)
            ->where('label', 'inspection')
            ->distinct('media_item_id')
            ->count('media_item_id');

        if ($photosCount < 4) {
            return $this->jsonOrBack($request, false, "Minimum 4 photos required. Currently: {$photosCount}/4.", [
                'photos_count' => $photosCount,
                'locked'       => false,
            ]);
        }

        // ✅ Ensure checklist exists (basic integrity)
        $itemsCount = \App\Models\JobInspectionItem::query()
            ->where('garage_id', $garageId)
            ->where('inspection_id', $inspection->id)
            ->count();

        if ($itemsCount < 58) {
            return $this->jsonOrBack($request, false, 'Checklist not ready. Please refresh and try again.', [
                'items_count' => $itemsCount,
                'locked'      => false,
            ]);
        }

        // ✅ Mark inspection complete
        $inspection->update([
            'status'       => 'completed',
            'completed_at' => now(),
            'completed_by' => auth()->id(),
        ]);

        // ✅ After completing, ensure job link again (in case it was missing before)
        if ($jobId > 0 && empty($inspection->job_id)) {
            $inspection->update(['job_id' => $jobId]);
        }

        // ✅ DB truth gates: THIS is what quotation card reads on reload
        if ($jobId > 0) {
            \App\Models\Job::where('garage_id', $garageId)
                ->where('id', $jobId)
                ->update([
                    'inspection_completed_at' => $inspection->completed_at ?? now(),
                    'quotation_unlocked'      => true,
                ]);
        }

        $counts = $this->inspectionCounts($inspection->id);

        return $this->jsonOrBack($request, true, 'Inspection marked complete and locked.', array_merge($counts, [
            'locked'       => true,
            'job_id'       => $jobId,
            'completed_at' => ($inspection->completed_at ?? now())->toDateTimeString(),
        ]));
    }



    /**
     * Helper: unified JSON / redirect response
     */
    private function jsonOrBack(Request $request, bool $ok, string $message, array $extra = []): JsonResponse|RedirectResponse
    {
        $isAjax = $request->ajax() || $request->expectsJson();

        if ($isAjax) {
            return response()->json(array_merge([
                'ok'      => $ok,
                'message' => $message,
            ], $extra), $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }

    /**
     * Helper: counts for UI (photos + changes)
     */
    private function inspectionCounts(int $inspectionId): array
    {
        $garageId = auth()->user()->garage_id;

        $photosCount = \App\Models\MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspectionId)
            ->where('label', 'inspection_photo')
            ->count();

        $items = \App\Models\JobInspectionItem::query()
            ->where('garage_id', $garageId)
            ->where('inspection_id', $inspectionId)
            ->get(['state','notes']);

        $changes = 0;
        foreach ($items as $it) {
            if (($it->state ?? 'ok') !== 'ok') $changes++;
            if (!empty(trim((string)($it->notes ?? '')))) $changes++;
        }

        return [
            'photos_count' => $photosCount,
            'changes'      => $changes,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | INTERNAL: Inspection bootstrap for Step 3 (insurance)
    |--------------------------------------------------------------------------
    */
    private function ensureDraftUuid(Request $request, array &$draft): string
    {
        if (empty($draft['draft_uuid'])) {
            $draft['draft_uuid'] = (string) Str::uuid();
            $this->putDraft($request, $draft);
        }

        return $draft['draft_uuid'];
    }

    private function ensureInsuranceInspection(Request $request, array $draft): ?\App\Models\JobInspection
    {
        $garageId = auth()->user()->garage_id;

        // Draft details (session-backed)
        $details = $draft['details'] ?? [];
        $currentId = (int) ($details['inspection_id'] ?? 0);

        // ✅ If we're on insurance show page, we pass ctx=['job'=>ID]
        $jobId = (int) $request->input('job');

        // Helper: score an inspection by "how much real work it contains"
        $score = function (? \App\Models\JobInspection $insp) use ($garageId): int {
            if (!$insp) return -1;

            $photos = \App\Models\MediaAttachment::query()
                ->where('garage_id', $garageId)
                ->where('attachable_type', \App\Models\JobInspection::class)
                ->where('attachable_id', $insp->id)
                ->where('label', 'inspection_photo')
                ->count();

            $items = \App\Models\JobInspectionItem::query()
                ->where('garage_id', $garageId)
                ->where('inspection_id', $insp->id)
                ->count();

            $completedBonus = (($insp->status ?? 'draft') === 'completed') ? 1000 : 0;

            // photos weigh more than items for your gates
            return $completedBonus + ($photos * 10) + $items;
        };

        // Load candidate A = "draft-linked inspection"
        $draftInspection = null;
        if ($currentId > 0) {
            $draftInspection = \App\Models\JobInspection::query()
                ->where('garage_id', $garageId)
                ->where('id', $currentId)
                ->first();
        }

        // Load candidate B = "job-linked inspection" (latest)
        $jobInspection = null;
        if ($jobId > 0) {
            $jobInspection = \App\Models\JobInspection::query()
                ->where('garage_id', $garageId)
                ->where('job_id', $jobId)
                ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")
                ->orderByDesc('id')
                ->first();
        }

        // ✅ Decide canonical inspection
        $pick = null;

        if ($jobId > 0) {
            // Prefer the one with real work (photos/items/completed)
            $a = $score($draftInspection);
            $b = $score($jobInspection);

            $pick = ($draftInspection && $a >= $b) ? $draftInspection : $jobInspection;

            // If neither exists, create one attached to job
            if (!$pick) {
                $pick = \App\Models\JobInspection::create([
                    'garage_id'    => $garageId,
                    'job_id'       => $jobId,
                    'draft_uuid'   => $details['draft_uuid'] ?? null,
                    'type'         => 'check_in',
                    'status'       => 'draft',
                    'completed_at' => null,
                    'completed_by' => null,
                ]);
            }

            // ✅ Ensure canonical inspection is linked to this job
            if (empty($pick->job_id)) {
                $pick->update(['job_id' => $jobId]);
            }

            // ✅ If we now have a duplicate "empty draft" inspection for this job, delete it
            if ($jobInspection && $pick && $jobInspection->id !== $pick->id) {
                // Only delete if it's basically empty + draft
                $jobPhotos = \App\Models\MediaAttachment::query()
                    ->where('garage_id', $garageId)
                    ->where('attachable_type', \App\Models\JobInspection::class)
                    ->where('attachable_id', $jobInspection->id)
                    ->where('label', 'inspection_photo')
                    ->count();

                $jobItems = \App\Models\JobInspectionItem::query()
                    ->where('garage_id', $garageId)
                    ->where('inspection_id', $jobInspection->id)
                    ->count();

                if (($jobInspection->status ?? 'draft') === 'draft' && $jobPhotos === 0 && $jobItems === 0) {
                    $jobInspection->delete();
                }
            }

            // ✅ Keep draft pointer in sync (so ALL endpoints operate on same inspection)
            $details['inspection_id'] = $pick->id;
            $draft['details'] = $details;
            $this->putDraft($request, $draft);

            // ✅ Sync job gate timestamp if inspection is already completed
            if (($pick->status ?? 'draft') === 'completed') {
                \App\Models\Job::where('garage_id', $garageId)
                    ->where('id', $jobId)
                    ->update([
                        'inspection_completed_at' => $pick->completed_at ?? now(),
                    ]);
            }

            return $pick;
        }

        // ------------------------------------------------------------
        // No job context (pure wizard flow) -> fallback to original logic
        // ------------------------------------------------------------
        if ($draftInspection) return $draftInspection;

        $draftUuid = $details['draft_uuid'] ?? null;
        if (!$draftUuid) return null;

        $inspection = \App\Models\JobInspection::create([
            'garage_id'    => $garageId,
            'job_id' => $jobId,
            'draft_uuid'   => $draftUuid,
            'type'         => 'check_in',
            'status'       => 'draft',
            'completed_at' => null,
            'completed_by' => null,
        ]);

        $details['inspection_id'] = $inspection->id;
        $draft['details'] = $details;
        $this->putDraft($request, $draft);
        
        return $inspection;
    }

    private function ensureDraftJob(Request $request, array $draft = []): void
    {
        $garageId = auth()->user()->garage_id;

        // ✅ Always use DB draft as the source of truth
        $d = $this->ensureDbDraft($request);

        // Already linked? nothing to do.
        if (!empty($d->job_id)) {
            return;
        }

        if (empty($d->vehicle_id)) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'vehicle_id' => 'Vehicle is required.',
            ]);
        }

        // Vehicle = source of truth for customer
        $vehicle = \App\Models\Vehicle::where('garage_id', $garageId)
            ->findOrFail((int) $d->vehicle_id);

        $customerId = $vehicle->customer_id ?: $d->customer_id;

        if (!$customerId) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'customer_id' => 'Customer is required.',
            ]);
        }

        $payerType = (string) ($d->payer_type ?? 'individual');

        // Only company uses organization_id at draft stage
        $orgId = null;
        if ($payerType === 'company') {
            $payer = is_array($d->payer ?? null) ? $d->payer : [];
            if (!empty($payer['organization_id'])) {
                $orgId = (int) $payer['organization_id'];
            }
        }

        // ✅ Create exactly one draft job (payer_type MUST persist correctly)
        $job = \App\Models\Job::create([
            'garage_id'       => $garageId,
            'vehicle_id'      => $vehicle->id,
            'customer_id'     => $customerId,
            'created_by'      => auth()->id(),

            'job_number'      => null,    // draft
            'status'          => 'draft',

            'payer_type'      => $payerType,
            'organization_id' => $orgId,
        ]);

        // ✅ Persist link to DB draft
        $d->job_id = $job->id;
        $d->save();
    }



    public function vaultPicker(Request $request): JsonResponse
    {
        try {
            $draft = $this->draft($request);
            if (($draft['payer_type'] ?? null) !== 'insurance') {
                return response()->json(['html' => '<div class="text-sm text-red-600">Forbidden.</div>'], 403);
            }

            $this->persistContext($request);
            $ctx = $this->carryContext($request);

            $garageId = auth()->user()->garage_id;

            $q = trim((string) $request->query('q', ''));
            $itemsQuery = MediaItem::query()
                ->where('garage_id', $garageId)
                ->orderByDesc('id');

            if ($q !== '') {
                $itemsQuery->where('original_name', 'like', '%' . $q . '%');
            }

            $items = $itemsQuery->limit(60)->get();

            $html = view('jobs.insurance.components._vault_picker', [
                'items' => $items,
                'q'     => $q,
                'ctx'   => $ctx,
            ])->render();

            return response()->json(['html' => $html]);

        } catch (\Throwable $e) {
            \Log::error('vaultPicker failed', [
                'message' => $e->getMessage(),
                'trace'   => substr($e->getTraceAsString(), 0, 1200),
            ]);

            return response()->json([
                'html' => '<div class="text-sm text-red-600">Vault error. Check logs.</div>',
            ], 500);
        }
    }

    public function vaultUpload(Request $request): JsonResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') abort(403);

        $this->persistContext($request);
        $ctx = $this->carryContext($request);

        $request->validate([
            'photo' => ['required', 'image', 'max:5120'],
        ]);

        $garageId = auth()->user()->garage_id;
        $file = $request->file('photo');

        $disk = 'public';
        $dir  = "garages/{$garageId}/vault/images";
        $path = $file->store($dir, $disk);

        [$width, $height] = @getimagesize($file->getRealPath()) ?: [null, null];

        $item = MediaItem::create([
            'garage_id'      => $garageId,
            'media_uuid'     => (string) Str::uuid(),
            'disk'           => $disk,
            'path'           => $path,
            'original_name'  => $file->getClientOriginalName(),
            'mime_type'      => $file->getMimeType(),
            'size_bytes'     => $file->getSize(),
            'width'          => $width,
            'height'         => $height,
        ]);

        // Refresh list for modal
        $items = MediaItem::query()
            ->where('garage_id', $garageId)
            ->orderByDesc('id')
            ->limit(60)
            ->get();

        $html = view('jobs.insurance.components._vault_picker', [
            'items' => $items,
            'q'     => '',
            'ctx'   => $ctx,
        ])->render();

        return response()->json([
            'ok'            => true,
            'media_item_id' => $item->id,
            'html'          => $html,
        ]);
    }


    public function inspectionChecklistSave(Request $request)
    {
        $draft = $this->draft($request);

        if (($draft['payer_type'] ?? null) !== 'insurance') {
            abort(403);
        }

        // Make sure we have a draft UUID + an inspection header row
        $this->ensureDraftUuid($request, $draft);
        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return response()->json([
                'ok' => false,
                'message' => 'Inspection not available.',
            ], 422);
        }

        if (($inspection->status ?? null) === 'completed') {
            return response()->json([
                'ok' => false,
                'message' => 'Inspection is completed and locked.',
            ], 422);
        }

        // Validate incoming payload
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*.state' => ['nullable', 'in:ok,damaged,missing'],
            'items.*.notes' => ['nullable', 'string'],
        ]);

        $items = $data['items'] ?? [];

        // Build rows for upsert (one row per item_no)
        $now = now();
        $rows = [];

        foreach ($items as $itemNo => $row) {
            $no = (int) $itemNo;
            if ($no < 1 || $no > 58) continue;

            $state = $row['state'] ?? null;
            $notes = $row['notes'] ?? null;

            // label: use your config list (recommended), fallback to blank
            $labelMap = config('inspection_checklists.dalima_checkin_checkout_v1', []);
            $label = $labelMap[$no] ?? (string)($labelMap[(string)$no] ?? '');

            $rows[] = [
                'garage_id'      => $inspection->garage_id,
                'inspection_id'  => $inspection->id,
                'item_no'        => $no,
                'label'          => $label,
                'state'          => $state ?: null,
                'notes'          => $notes ? trim((string)$notes) : null,
                'created_at'     => $now,
                'updated_at'     => $now,
            ];
        }

        if (!count($rows)) {
            return response()->json([
                'ok' => false,
                'message' => 'No checklist items received.',
            ], 422);
        }

        // ✅ Upsert into job_inspection_items
        // Unique key is (inspection_id, item_no) per your migration.
        \DB::table('job_inspection_items')->upsert(
            $rows,
            ['inspection_id', 'item_no'],
            ['garage_id', 'label', 'state', 'notes', 'updated_at']
        );

        // Recalculate done count
        $done = \DB::table('job_inspection_items')
            ->where('inspection_id', $inspection->id)
            ->whereNotNull('state')
            ->count();

        // Photos count (if you have media relation, keep that; otherwise return current)
        $photosCount = method_exists($inspection, 'media')
            ? $inspection->media()->count()
            : 0;

        return response()->json([
            'ok' => true,
            'doneItems' => (int) $done,
            'photos_count' => (int) $photosCount,
            'message' => 'Checklist saved.',
        ]);
    }


    public function vaultAttachToInspection(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') abort(403);

        $garageId = auth()->user()->garage_id;

        // Ensure inspection exists
        $this->ensureDraftUuid($request, $draft);

        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return $this->vaultAttachRespond($request, false, 'Inspection not available.');
        }

        if (($inspection->status ?? null) === 'completed') {
            return $this->vaultAttachRespond($request, false, 'Inspection is completed and locked; cannot attach photos.');
        }

        // ✅ Accept BOTH:
        // - media_item_id (single)
        // - media_item_ids (multi)
        $data = $request->validate([
            'media_item_id'      => ['nullable', 'integer'],
            'media_item_ids'     => ['nullable', 'array'],
            'media_item_ids.*'   => ['integer'],
        ]);

        $ids = [];

        if (!empty($data['media_item_id'])) {
            $ids[] = (int) $data['media_item_id'];
        }

        if (!empty($data['media_item_ids']) && is_array($data['media_item_ids'])) {
            foreach ($data['media_item_ids'] as $id) {
                $ids[] = (int) $id;
            }
        }

        $ids = array_values(array_unique(array_filter($ids)));

        if (count($ids) === 0) {
            return $this->vaultAttachRespond($request, false, 'No media selected.');
        }

        // Ensure all media items belong to this garage
        $mediaItems = \App\Models\MediaItem::query()
            ->where('garage_id', $garageId)
            ->whereIn('id', $ids)
            ->get(['id', 'disk', 'path', 'original_name']);

        if ($mediaItems->count() === 0) {
            return $this->vaultAttachRespond($request, false, 'Selected media not found for this garage.');
        }

        // Attach each (skip duplicates)
        $attachedNew = 0;

        foreach ($mediaItems as $media) {
            $exists = \App\Models\MediaAttachment::query()
                ->where('garage_id', $garageId)
                ->where('media_item_id', $media->id)
                ->where('attachable_type', \App\Models\JobInspection::class)
                ->where('attachable_id', $inspection->id)
                ->where('label', 'inspection_photo')
                ->exists();

            if (!$exists) {
                \App\Models\MediaAttachment::create([
                    'garage_id'       => $garageId,
                    'media_item_id'   => $media->id,
                    'attachable_type' => \App\Models\JobInspection::class,
                    'attachable_id'   => $inspection->id,
                    'label'           => 'inspection_photo',
                ]);
                $attachedNew++;
            }
        }

        // Recount photos after attach
        $photosCount = \App\Models\MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo')
            ->count();

        // ✅ Return refreshed thumbs (MUST include attachment_id for reliable detach)
        $attached = \App\Models\MediaAttachment::query()
            ->where('media_attachments.garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo')
            ->join('media_items', 'media_items.id', '=', 'media_attachments.media_item_id')
            ->orderByDesc('media_attachments.id')
            ->limit(12)
            ->get([
                'media_attachments.id as attachment_id',
                'media_items.id as id',
                'media_items.disk as disk',
                'media_items.path as path',
                'media_items.original_name as original_name',
            ])
            ->map(function ($row) {
                $disk = $row->disk ?: 'public';
                return [
                    'attachment_id' => (int) $row->attachment_id,
                    'id'            => (int) $row->id, // legacy key = media_item_id
                    'url'           => \Storage::disk($disk)->url($row->path),
                    'thumb_url'     => \Storage::disk($disk)->url($row->path),
                    'original_name' => $row->original_name,
                ];
            })
            ->values()
            ->all();

        $msg = $attachedNew > 0
            ? "Attached {$attachedNew} photo(s)."
            : "All selected photos were already attached.";

        return $this->vaultAttachRespond($request, true, $msg, [
            'photos_count' => $photosCount,
            'photosCount'  => $photosCount, // compatibility
            'attached'     => $attached,
        ]);
    }

    public function vaultDetachFromInspection(Request $request): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        $draft = $this->draft($request);
        if (($draft['payer_type'] ?? null) !== 'insurance') {
            abort(403);
        }

        $garageId = auth()->user()->garage_id;

        // ✅ Ensure inspection exists (draft-scoped)
        $this->ensureDraftUuid($request, $draft);
        $inspection = $this->ensureInsuranceInspection($request, $draft);

        if (!$inspection) {
            return $this->vaultAttachRespond($request, false, 'Inspection not available.');
        }

        // ✅ Lock rule
        if (($inspection->status ?? null) === 'completed') {
            return $this->vaultAttachRespond($request, false, 'Inspection is completed and locked; cannot remove photos.');
        }

        $data = $request->validate([
            // ✅ Support BOTH payload shapes:
            // - old UI sends: media_item_id
            // - new UI can send: attachment_id
            'media_item_id'  => ['nullable', 'integer'],
            'attachment_id'  => ['nullable', 'integer'],
        ]);

        $mediaItemId = isset($data['media_item_id']) && $data['media_item_id']
            ? (int) $data['media_item_id']
            : null;

        $attachmentId = isset($data['attachment_id']) && $data['attachment_id']
            ? (int) $data['attachment_id']
            : null;

        if (!$mediaItemId && !$attachmentId) {
            return $this->vaultAttachRespond($request, false, 'Missing media_item_id or attachment_id.');
        }

        // ✅ Detach only for this inspection + label (safe + scoped)
        $q = \App\Models\MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo');

        if ($attachmentId) {
            $q->where('id', $attachmentId);
        } else {
            $q->where('media_item_id', $mediaItemId);
        }

        $q->delete();

        // ✅ Recount photos after detach
        $photosCount = \App\Models\MediaAttachment::query()
            ->where('garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo')
            ->count();

        // ✅ Return refreshed thumbs (same shape as attach)
        // Include attachment_id so UI can detach precisely (even if duplicates exist)
        $attached = \App\Models\MediaAttachment::query()
            ->where('media_attachments.garage_id', $garageId)
            ->where('attachable_type', \App\Models\JobInspection::class)
            ->where('attachable_id', $inspection->id)
            ->where('label', 'inspection_photo')
            ->join('media_items', 'media_items.id', '=', 'media_attachments.media_item_id')
            ->orderByDesc('media_attachments.id')
            ->limit(12)
            ->get([
                'media_attachments.id as attachment_id',
                'media_items.id as id',
                'media_items.disk as disk',
                'media_items.path as path',
                'media_items.original_name as original_name',
            ])
            ->map(function ($row) {
                $disk = $row->disk ?: 'public';
                return [
                    // ✅ both keys for compatibility
                    'attachment_id' => (int) $row->attachment_id,
                    'id'            => (int) $row->id, // media_item_id (legacy key)
                    'url'           => \Storage::disk($disk)->url($row->path),
                    'thumb_url'     => \Storage::disk($disk)->url($row->path), // if you later generate thumbs, swap here
                    'original_name' => $row->original_name,
                ];
            })
            ->values()
            ->all();

        return $this->vaultAttachRespond($request, true, 'Photo removed.', [
            'photos_count' => $photosCount,
            'photosCount'  => $photosCount, // compatibility
            'attached'     => $attached,
        ]);
    }


    /**
     * Respond helper:
     * - If AJAX/JSON: return JSON payload used by Vault modal
     * - Else: normal redirect back with flash message (fallback)
     */
    private function vaultAttachRespond(
        Request $request,
        bool $ok,
        string $message,
        array $extra = []
    ): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse {
        $isAjax = $request->ajax() || $request->expectsJson();

        if ($isAjax) {
            return response()->json(array_merge([
                'ok'      => $ok,
                'message' => $message,
            ], $extra), $ok ? 200 : 422);
        }

        return $ok
            ? back()->with('success', $message)
            : back()->with('error', $message);
    }


    private function resetDraft(Request $request, bool $keepContext = true): void
    {
        $existing = $this->draft($request);

        $fresh = [
            'customer_id' => $keepContext ? ($existing['customer_id'] ?? null) : null,
            'vehicle_id'  => $keepContext ? ($existing['vehicle_id'] ?? null) : null,
            'modal'       => $keepContext ? ($existing['modal'] ?? null) : null,
            'last_job_id' => null,

            'payer_type'  => null,
            'payer'       => [],
            'details'     => [],

            // ✅ CRITICAL: ALWAYS reset inspection anchor for a NEW wizard session
            // Otherwise checklist/photos bleed into next job.
            'inspection_id' => null,

            // ✅ CRITICAL: ALWAYS generate a new draft UUID for a NEW wizard session
            'draft_uuid' => (string) \Illuminate\Support\Str::uuid(),
        ];

        $this->putDraft($request, $fresh);
    }

    private function finalizeDraftJob(Request $request, array $draft): Job
    {
        $garageId = auth()->user()->garage_id;

        $jobId = (int) ($draft['job_id'] ?? 0);
        if ($jobId <= 0) {
            throw new \RuntimeException('Missing draft job_id.');
        }

        /** @var \App\Models\Job $job */
        $job = Job::query()
            ->where('garage_id', $garageId)
            ->findOrFail($jobId);

        // Build payload using your canonical builder (same as store())
        $payload = app(\App\Http\Controllers\JobController::class)->buildCreatePayload($request);

        return DB::transaction(function () use ($job, $payload, $draft) {

            // Ensure job_number exists (do NOT burn numbers on drafts unless finalizing)
            if (empty($job->job_number)) {
                $job->job_number = app(\App\Http\Controllers\JobController::class)
                    ->generateJobNumber($job->garage_id);
            }

            // Core job fields finalized
            $job->fill([
                'job_date'       => $payload['job_date'] ?? $job->job_date,
                'service_type'   => $payload['service_type'] ?? $job->service_type,
                'complaint'      => $payload['complaint'] ?? $job->complaint,
                'diagnosis'      => $payload['diagnosis'] ?? $job->diagnosis,
                'work_done'      => $payload['work_done'] ?? $job->work_done,
                'parts_used'     => $payload['parts_used'] ?? $job->parts_used,
                'mileage'        => $payload['mileage'] ?? $job->mileage,

                'labour_cost'    => $payload['labour_cost'] ?? $job->labour_cost,
                'parts_cost'     => $payload['parts_cost'] ?? $job->parts_cost,
                'estimated_cost' => $payload['estimated_cost'] ?? $job->estimated_cost,
                'final_cost'     => $payload['final_cost'] ?? $job->final_cost,

                // Finalize status
                'status'         => $payload['status'] ?? 'pending',
                'notes'          => $payload['notes'] ?? $job->notes,
            ]);

            // Payer context (already mostly set at draft create time, but safe to enforce)
            $job->payer_type = $payload['payer_type'] ?? $job->payer_type;
            $job->organization_id = $payload['organization_id'] ?? $job->organization_id;

            $job->save();

            // Sync insurance details using your existing canonical method
            if (($job->payer_type ?? null) === 'insurance' && isset($payload['insurance'])) {
                app(\App\Http\Controllers\JobController::class)
                    ->syncInsuranceDetailsForJob($job->id, $job->payer_type, $payload['insurance'] ?? null);
            }

            // Sync part items (if provided)
            if (isset($payload['part_items']) && is_array($payload['part_items'])) {
                app(\App\Http\Controllers\JobController::class)->syncJobPartItems($job, $payload['part_items']);
            }

            return $job;
        });
    }


    public function step3Quotation(Request $request)
    {
        $garageId = auth()->user()->garage_id;
        $jobId = (int) $request->query('job_id');

        $job = Job::query()
            ->where('garage_id', $garageId)
            ->findOrFail($jobId);

        // v1: one active quotation (version 1)
        $quotation = JobQuotation::query()->firstOrCreate(
            ['garage_id' => $garageId, 'job_id' => $job->id, 'version' => 1],
            ['status' => 'draft']
        );

        $lines = JobQuotationLine::query()
            ->where('quotation_id', $quotation->id)
            ->orderBy('sort_order')
            ->get();

        // Adjust view name to your actual quotation blade file
        return view('jobs.create.partials.step-3-quotation', [
            'job' => $job,
            'quotation' => $quotation,
            'lines' => $lines,
            'editable' => $quotation->status === 'draft',
        ]);
    }

    public function saveQuotation(Request $request): JsonResponse
    {
        \Log::info('QUOTATION_SAVE_HIT', [
            'user_id' => auth()->id(),
            'garage_id' => auth()->user()->garage_id ?? null,
            'job_id' => request('job_id'),
            'action' => request('action'),
        ]);

        $garageId = auth()->user()->garage_id;

        $data = $request->validate([
            'job_id' => ['required','integer'],

            'lines' => ['array'],
            'lines.*.type' => ['nullable','string','max:20'],
            'lines.*.category' => ['nullable','string','max:100'],
            'lines.*.description' => ['required','string','max:255'],
            'lines.*.qty' => ['nullable','numeric','min:0'],
            'lines.*.amount' => ['nullable','numeric','min:0'],

            'tax' => ['nullable','numeric','min:0'],
            'discount' => ['nullable','numeric','min:0'],

            // optional: UI can pass action=submit to lock it
            'action' => ['nullable','string'], // save|submit
        ]);

        $job = Job::query()
            ->where('garage_id', $garageId)
            ->findOrFail((int) $data['job_id']);
            
        $allowedTypes = ['labour','parts','materials','sublet'];
        $action = $data['action'] ?? 'save';

        // ✅ HARD GATE (action-aware): insurance rules
        if ($job->payer_type === 'insurance') {
            $gate = app(\App\Services\InsuranceGate::class);

            // save/edit => canEditQuote
            // submit     => canSubmitQuote (stricter, correct reason)
            $r = ($action === 'submit')
                ? $gate->canSubmitQuote($job)
                : $gate->canEditQuote($job);

            if (!($r['ok'] ?? false)) {
                return response()->json([
                    'ok' => false,
                    'message' => $r['reason'] ?? 'Quotation is locked.',
                    'code' => $r['code'] ?? 'quote.locked',
                ], 403);
            }
        }


        return DB::transaction(function () use ($garageId, $job, $data, $allowedTypes, $action) {

            $q = JobQuotation::query()->firstOrCreate(
                ['garage_id' => $garageId, 'job_id' => $job->id, 'version' => 1],
                ['status' => 'draft']
            );

            if ($q->status !== 'draft') {
                $when = data_get($q, 'submitted_at')
                    ? \Illuminate\Support\Carbon::parse($q->submitted_at)->format('d M Y, H:i')
                    : null;

                $msg = match ((string) $q->status) {
                    'submitted' => 'Quotation already submitted' . ($when ? " ({$when})." : '.') . ' It is locked for edits.',
                    'approved'  => 'Quotation already approved. It is locked.',
                    default     => 'Quotation is locked.',
                };

                return response()->json([
                    'ok' => false,
                    'message' => $msg,
                    'status' => $q->status,
                ], 403);
            }


            $lines = collect($data['lines'] ?? [])
                ->values()
                ->map(function ($l, $idx) use ($allowedTypes) {

                    $desc = trim((string)($l['description'] ?? ''));
                    $qty = (float)($l['qty'] ?? 1);
                    if ($qty <= 0) $qty = 1;

                    $amount = round((float)($l['amount'] ?? 0), 2);

                    // amount-first: derive unit_price (internal only)
                    $unit = $qty > 0 ? round($amount / $qty, 2) : 0;

                    $type = $l['type'] ?? 'labour';
                    $type = in_array($type, $allowedTypes, true) ? $type : 'labour';

                    return [
                        'type' => $type,
                        'category' => $l['category'] ?? null,
                        'description' => $desc,
                        'qty' => round($qty, 2),
                        'unit_price' => $unit,
                        'amount' => $amount,
                        'sort_order' => $idx,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })
                ->filter(fn($row) => $row['description'] !== '')
                ->all();

            $subtotal = round(array_sum(array_column($lines, 'amount')), 2);
            $tax = round((float)($data['tax'] ?? 0), 2);
            $discount = round((float)($data['discount'] ?? 0), 2);
            $total = round(max(0, $subtotal + $tax - $discount), 2);

            $q->update([
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
            ]);

            // ✅ Create/Upsert Approval Pack (canonical approval state lives in approval_packs)
            $pack = ApprovalPack::query()
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->orderByDesc('id')
                ->first();

            if (!$pack) {
                ApprovalPack::create([
                    'garage_id'    => $garageId,
                    'job_id'       => $job->id,
                    'quotation_id' => $q->id,
                    'status'       => 'submitted',
                    'version'      => 1,
                    'total_amount' => (float) ($q->total ?? 0),
                    'currency'     => 'KES',
                    'generated_by' => auth()->id(),
                    'generated_at' => now(),
                    'submitted_at' => now(),
                    // decision_at stays NULL until approve/reject
                ]);
            } else {
                // If pack exists, keep it aligned to this submitted quotation
                if (in_array($pack->status, ['draft', 'submitted'], true)) {
                    $pack->update([
                        'quotation_id' => $q->id,
                        'status'       => 'submitted',
                        'submitted_at' => $pack->submitted_at ?: now(),
                        'total_amount' => (float) ($q->total ?? $pack->total_amount ?? 0),
                    ]);
                }
            }

            // simplest v1 persistence: replace all lines
            JobQuotationLine::query()
                ->where('quotation_id', $q->id)
                ->delete();

            if (!empty($lines)) {
                foreach ($lines as &$row) {
                    $row['quotation_id'] = $q->id;
                    $row['garage_id']    = $q->garage_id; // ✅ enforce tenancy
                }
                JobQuotationLine::query()->insert($lines);
            }


            if ($action === 'submit') {

                // ✅ Insurance jobs must have insurance details before quotation submit
                if ($job->payer_type === 'insurance') {
                    $hasDetails = \App\Models\JobInsuranceDetail::query()
                        ->where('job_id', $job->id)
                        ->exists();

                    if (!$hasDetails) {
                        return response()->json([
                            'ok' => false,
                            'message' => 'Insurance details missing. Please complete insurer/policy/claim details before submitting quotation.',
                        ], 422);
                    }
                }

                $q->update([
                    'status' => 'submitted',
                    'submitted_at' => now(),
                    'submitted_by' => auth()->id(),
                ]);
            }


            return response()->json([
                'ok' => true,
                'quotation_id' => $q->id,
                'status' => $q->status,
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
            ]);
        });
    }

}

<?php

namespace App\Services;

use App\Models\JobDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class JobDraftService
{
    /**
     * Resolve garage_id from the app in a safe, non-opinionated way.
     * Adjust ONLY this method if your app uses a single canonical garage resolver.
     */
    protected function garageId(Request $request): int
    {
        // 1) If you bind a "current garage" object in the container
        //    e.g. app('garage'), app('currentGarage'), app('tenant'), etc.
        foreach (['currentGarage', 'garage', 'tenant'] as $key) {
            if (app()->bound($key)) {
                $obj = app($key);
                if (is_object($obj) && isset($obj->id) && $obj->id) {
                    return (int) $obj->id;
                }
            }
        }

        // 2) If the authenticated user has garage_id
        $user = $request->user();
        if ($user && isset($user->garage_id) && $user->garage_id) {
            return (int) $user->garage_id;
        }

        // 3) If middleware stores current garage id in session
        $sid = $request->session()->get('garage_id');
        if ($sid) {
            return (int) $sid;
        }

        // 4) If request has it (rare, but safe)
        if ($request->filled('garage_id')) {
            return (int) $request->input('garage_id');
        }

        throw new \RuntimeException('Unable to resolve current garage_id for JobDraftService.');
    }

    protected function userId(Request $request): ?int
    {
        return $request->user()?->id;
    }

    /**
     * Get active draft or create new
     */
    public function currentOrCreate(Request $request): JobDraft
    {
        $garageId = $this->garageId($request);
        $userId   = $this->userId($request);

        $draft = JobDraft::where('garage_id', $garageId)
            ->when($userId, fn($q) => $q->where('user_id', $userId))
            ->where('status', 'draft')
            ->latest()
            ->first();

        if ($draft) {
            return $draft;
        }

        return JobDraft::create([
            'garage_id'  => $garageId,
            'user_id'    => $userId,
            'draft_uuid' => (string) Str::uuid(),
            'status'     => 'draft',
        ]);
    }

    /**
     * Load draft safely (tenant scoped).
     * NOTE: This loads only active (status=draft) drafts.
     */
    public function loadOrFail(Request $request, string $uuid): JobDraft
    {
        $garageId = $this->garageId($request);

        $draft = JobDraft::where('draft_uuid', $uuid)
            ->where('garage_id', $garageId)
            ->where('status', 'draft')
            ->first();

        if (!$draft) {
            throw (new ModelNotFoundException)->setModel(JobDraft::class, [$uuid]);
        }

        return $draft;
    }

    /**
     * Merge into details JSON
     */
    public function mergeDetails(JobDraft $draft, array $patch): JobDraft
    {
        $current = $draft->details ?? [];
        $draft->details = array_replace_recursive($current, $patch);
        $draft->save();

        return $draft;
    }

    /**
     * Set payer info (type + payer json).
     * If payer type changes, payer is wiped to avoid mixing.
     */
    public function setPayerType(JobDraft $draft, string $payerType): JobDraft
    {
        if ($draft->payer_type && $draft->payer_type !== $payerType) {
            $draft->payer = [];
        }

        $draft->payer_type = $payerType;
        $draft->save();

        return $draft;
    }

    public function setPayer(JobDraft $draft, array $payerData): JobDraft
    {
        $draft->payer = $payerData;
        $draft->save();

        return $draft;
    }

    /**
     * Quick resume context
     */
    public function setCustomerVehicle(JobDraft $draft, ?int $customerId, ?int $vehicleId): JobDraft
    {
        $draft->customer_id = $customerId;
        $draft->vehicle_id  = $vehicleId;
        $draft->save();

        return $draft;
    }

    /**
     * Update resume pointer
     */
    public function touchStep(JobDraft $draft, string $step): JobDraft
    {
        $draft->last_step = $step;
        $draft->touch();

        return $draft;
    }

    public function abandon(JobDraft $draft): void
    {
        $draft->status = 'abandoned';
        $draft->save();
    }

    public function markSubmitted(JobDraft $draft, int $jobId): void
    {
        $draft->status = 'submitted';
        $draft->job_id = $jobId;
        $draft->save();
    }

    public static function ensureApproved(\App\Models\Job $job): void
    {
        if (($job->approval_status ?? null) !== 'approved') {
            abort(403, 'Insurance approval required before continuing.');
        }
    }

}

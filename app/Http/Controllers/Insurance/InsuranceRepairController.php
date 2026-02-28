<?php

namespace App\Http\Controllers\Insurance;

use App\Http\Controllers\Controller;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class InsuranceRepairController extends Controller
{
    private function authorizeGarage(Job $job): void
    {
        $garageId = Auth::user()->garage_id ?? null;
        abort_unless($garageId && (int) $job->garage_id === (int) $garageId, 403);
    }

    /**
     * Start repair (idempotent):
     * - Uses latest APPROVED pack (garage scoped)
     * - If a repair session already exists for that pack, do nothing
     * - Else create session + clone approval_pack_items into job_repair_items
     */
    public function start(Job $job)
    {
        $this->authorizeGarage($job);
        $garageId = (int) Auth::user()->garage_id;

        $pack = DB::table('approval_packs')
            ->where('garage_id', $garageId)
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->latest('id')
            ->first();

        if (! $pack) {
            return redirect()->route('jobs.insurance.show', $job)
                ->with('error', 'Repair cannot be started until the approval pack is approved.');
        }

        DB::transaction(function () use ($garageId, $job, $pack) {

            $existing = DB::table('job_repairs')
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->where('approval_pack_id', $pack->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return;
            }

            $now = now('UTC')->toDateTimeString();

            $repairId = DB::table('job_repairs')->insertGetId([
                'garage_id'        => $garageId,
                'job_id'           => $job->id,
                'approval_pack_id' => $pack->id,
                'status'           => 'in_progress',
                'started_by'       => Auth::id(),
                'started_at'       => $now,
                'created_at'       => $now,
                'updated_at'       => $now,
            ]);

            $ts = now('UTC')->toDateTimeString(); // single, consistent UTC timestamp

            DB::table('job_repair_items')->insertUsing(
                [
                    'garage_id','job_repair_id','approval_pack_item_id',
                    'line_type','name','description',
                    'approved_qty','approved_unit_price','approved_line_total',
                    'execution_status','created_at','updated_at'
                ],
                DB::table('approval_pack_items')
                    ->selectRaw(
                        '? as garage_id,
                        ? as job_repair_id,
                        id as approval_pack_item_id,
                        line_type,
                        name,
                        description,
                        qty as approved_qty,
                        unit_price as approved_unit_price,
                        line_total as approved_line_total,
                        "pending" as execution_status,
                        ? as created_at,
                        ? as updated_at',
                        [$garageId, $repairId, $ts, $ts]
                    )
                    ->where('garage_id', $garageId)
                    ->where('approval_pack_id', $pack->id)
            );
        });

        return redirect()->route('jobs.insurance.show', $job)
            ->with('success', 'Repair session is ready.');
    }

    /**
     * Update one repair item execution_status (Phase 5 safe)
     * Route: POST jobs/{job}/insurance/repair/item/{item}/status
     *
     * Allowed: pending | in_progress | done | skipped | not_done
     */
    public function updateStatus(Request $request, Job $job, $item)
    {
        $this->authorizeGarage($job);

        $garageId = (int) Auth::user()->garage_id;

        $data = $request->validate([
            'status'  => ['required', 'in:pending,in_progress,done,skipped,not_done'],
            'remarks' => ['nullable', 'string'],
        ]);

        $new = (string) $data['status'];

        $wantsJson = $request->expectsJson()
            || $request->ajax()
            || str_contains((string) $request->header('Accept'), 'application/json');

        return DB::transaction(function () use ($garageId, $job, $item, $new, $data, $wantsJson) {

            // ✅ Find latest repair session (prefer active/in_progress, else latest not completed)
            $repair = DB::table('job_repairs')
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->orderByDesc('id')
                ->first();

            if (! $repair) {
                $repair = DB::table('job_repairs')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $job->id)
                    ->whereNull('completed_at')
                    ->lockForUpdate()
                    ->orderByDesc('id')
                    ->first();
            }

            // ✅ Phase 5: never crash if no repair session exists
            if (! $repair) {
                $msg = 'No repair session found for this job.';
                if ($wantsJson) {
                    return response()->json(['ok' => false, 'message' => $msg], 404);
                }
                return redirect()->route('jobs.insurance.show', $job)->with('error', $msg);
            }

            // ✅ Lock the item row (must belong to current repair session)
            $row = DB::table('job_repair_items')
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repair->id)
                ->where('id', (int) $item)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                $msg = 'Repair item not found for this session.';
                if ($wantsJson) {
                    return response()->json(['ok' => false, 'message' => $msg], 404);
                }
                return redirect()->route('jobs.insurance.show', $job)->with('error', $msg);
            }

            // ✅ Timestamp rules
            $now = now()->toDateTimeString();

            $update = [
                'execution_status' => $new,
                'remarks'          => array_key_exists('remarks', $data)
                    ? ($data['remarks'] ?? null)
                    : ($row->remarks ?? null),
                'updated_at'       => $now,
            ];

            if ($new === 'in_progress') {
                $update['started_at']   = $row->started_at ?: $now;
                $update['completed_at'] = null;
            } elseif (in_array($new, ['done', 'skipped', 'not_done'], true)) {
                $update['started_at']   = $row->started_at ?: $now;
                $update['completed_at'] = $now;
            } elseif ($new === 'pending') {
                $update['started_at']   = null;
                $update['completed_at'] = null;
            }

            DB::table('job_repair_items')
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repair->id)
                ->where('id', (int) $item)
                ->update($update);

            $fresh = DB::table('job_repair_items')
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repair->id)
                ->where('id', (int) $item)
                ->first(['id', 'execution_status', 'started_at', 'completed_at', 'remarks']);

            if ($wantsJson) {
                return response()->json([
                    'ok'        => true,
                    'item'      => $fresh,
                    'repair_id' => $repair->id,
                ]);
            }

            return redirect()->route('jobs.insurance.show', $job)
                ->with('success', "Item updated: {$new}.");
        });
    }

    /**
     * Complete repair (Phase 5 safe)
     * - Requires an in-progress session
     * - Requires items exist
     * - Allows completion when ALL items are done OR skipped
     * Route: POST jobs/insurance/{job}/repair/complete
     */
    public function complete(Job $job)
    {

        \Log::info('REPAIR COMPLETE HIT', [
            'job_id' => $job->id,
            'user_id' => \Auth::id(),
            'garage_id' => \Auth::user()?->garage_id,
            'url' => request()->fullUrl(),
            'method' => request()->method(),
        ]);


        $this->authorizeGarage($job);
        $garageId = (int) Auth::user()->garage_id;

        $result = DB::transaction(function () use ($garageId, $job) {

            $repair = DB::table('job_repairs')
                ->where('garage_id', $garageId)
                ->where('job_id', $job->id)
                ->where('status', 'in_progress')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if (! $repair) {
                // If already completed, be friendly (no crash)
                $latest = DB::table('job_repairs')
                    ->where('garage_id', $garageId)
                    ->where('job_id', $job->id)
                    ->latest('id')
                    ->first();

                if ($latest && (($latest->status ?? null) === 'completed' || !is_null($latest->completed_at))) {
                    return ['ok' => true, 'msg' => 'Repair is already completed.'];
                }

                return ['ok' => false, 'msg' => 'No in-progress repair session found to complete.'];
            }

            $totalItems = DB::table('job_repair_items')
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repair->id)
                ->count();

            if ($totalItems <= 0) {
                return ['ok' => false, 'msg' => 'Repair cannot be completed: no repair items found in this session.'];
            }

            $notDone = DB::table('job_repair_items')
                ->where('garage_id', $garageId)
                ->where('job_repair_id', $repair->id)
                ->whereNotIn('execution_status', ['done', 'skipped', 'not_done'])
                ->count();

            if ($notDone > 0) {
                return ['ok' => false, 'msg' => "Repair cannot be completed: {$notDone} item(s) still pending/in progress."];
            }

            DB::table('job_repairs')
                ->where('garage_id', $garageId)
                ->where('id', $repair->id)
                ->update([
                    'status'       => 'completed',
                    'completed_at' => now(),
                    'updated_at'   => now(),
                ]);

            return ['ok' => true, 'msg' => 'Repair completed successfully.'];
        });

        return redirect()->route('jobs.insurance.show', $job)
            ->with($result['ok'] ? 'success' : 'error', $result['msg']);
    }
}

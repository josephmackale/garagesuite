<?php 

namespace App\Http\Controllers\Repair;

use App\Models\Job;
use App\Services\RepairService;

use App\Http\Controllers\Controller;
use App\Models\JobRepair;
use App\Models\JobRepairItem;
use Illuminate\Http\Request;

class JobRepairController extends Controller
{
    // Start Repair: Link the job to the latest approved pack
    public function startRepair($jobId)
    {
        $job = Job::findOrFail($jobId);

        try {
            $repairService = new RepairService();
            $jobRepair = $repairService->startFromLatestApprovedPack($job);

            return response()->json([
                'message' => 'Repair started successfully.',
                'job_repair' => $jobRepair
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        }
    }

    // Mark repair item as started
    public function itemStart($repairItemId)
    {
        $repairItem = JobRepairItem::findOrFail($repairItemId);

        // Update the status to in_progress
        $repairItem->update([
            'execution_status' => 'in_progress',
        ]);

        return response()->json([
            'message' => 'Repair item started.',
            'repair_item' => $repairItem
        ], 200);
    }

    // Mark repair item as done
    public function itemDone($repairItemId)
    {
        $repairItem = JobRepairItem::findOrFail($repairItemId);

        // Mark the item as completed
        $repairItem->update([
            'execution_status' => 'completed',
            'completed_at' => now(),
        ]);

        return response()->json([
            'message' => 'Repair item marked as done.',
            'repair_item' => $repairItem
        ], 200);
    }

    // Skip repair item (mark as not_done)
    public function itemSkip($repairItemId)
    {
        $repairItem = JobRepairItem::findOrFail($repairItemId);

        // Mark the item as skipped
        $repairItem->update([
            'execution_status' => 'not_done',
        ]);

        return response()->json([
            'message' => 'Repair item skipped.',
            'repair_item' => $repairItem
        ], 200);
    }

    // Undo changes to a repair item (mark as pending again)
    public function itemUndo($repairItemId)
    {
        $repairItem = JobRepairItem::findOrFail($repairItemId);

        // Reset item status to pending
        $repairItem->update([
            'execution_status' => 'pending',
            'assigned_technician_id' => null,  // Clear technician if assigned
        ]);

        return response()->json([
            'message' => 'Repair item reset to pending.',
            'repair_item' => $repairItem
        ], 200);
    }
}

<?php

namespace App\Services;

use App\Models\Job;
use App\Models\JobRepair;
use App\Models\JobRepairItem;
use App\Models\ApprovalPack;

class RepairService
{
    /**
     * Start the repair session from the latest approved pack.
     *
     * @param Job $job
     * @return JobRepair
     * @throws \Exception
     */
    public function startFromLatestApprovedPack(Job $job)
    {
        // Get the latest approved approval pack for this job (garage-scoped)
        $approvalPack = ApprovalPack::query()
            ->where('garage_id', $job->garage_id)
            ->where('job_id', $job->id)
            ->where('status', 'approved')
            ->with('approvalPackItems')
            ->latest('id')
            ->first();

        if (!$approvalPack) {
            throw new \Exception('No approved pack found for this job.');
        }

        // Create the repair session and link it to the approved pack
        $jobRepair = JobRepair::create([
            'garage_id'        => $job->garage_id,
            'job_id'           => $job->id,
            'approval_pack_id' => $approvalPack->id,
            'status'           => 'in_progress', // Initial status (standard)
        ]);

        // Create repair items based on the approval pack items
        $approvalPack->approvalPackItems->each(function ($item) use ($jobRepair) {
            JobRepairItem::create([
                'garage_id'             => $jobRepair->garage_id,
                'job_repair_id'         => $jobRepair->id,
                'approval_pack_item_id' => $item->id,
                'line_type'             => $item->line_type,
                'name'                  => $item->name,
                'description'           => $item->description,
                'approved_qty'          => $item->approved_qty,
                'approved_unit_price'   => $item->approved_unit_price,
                'approved_line_total'   => $item->approved_line_total,
                'execution_status'      => 'pending',
            ]);
        });

        return $jobRepair;
    }

}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Insurer;

class BackfillInsurers extends Command
{
    protected $signature = 'insurers:backfill {--dry-run : Show what would change without writing}';
    protected $description = 'Create garage-scoped insurers from job_insurance_details.insurer_name and set insurer_id.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');

        // If job_insurance_details doesn't have garage_id, we infer via jobs -> garage_id
        $rows = DB::table('job_insurance_details as jid')
            ->join('jobs as j', 'j.id', '=', 'jid.job_id')
            ->select('jid.id as jid_id', 'j.garage_id', 'jid.insurer_name')
            ->whereNull('jid.insurer_id')
            ->whereNotNull('jid.insurer_name')
            ->where('jid.insurer_name', '!=', '')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('Nothing to backfill.');
            return self::SUCCESS;
        }

        $this->info("Found {$rows->count()} job insurance rows to backfill." . ($dry ? ' (dry-run)' : ''));

        $updated = 0;

        foreach ($rows as $r) {
            $name = trim((string) $r->insurer_name);
            if ($name === '') continue;

            $insurer = Insurer::firstOrCreate(
                ['garage_id' => (int) $r->garage_id, 'name' => $name],
                ['is_active' => true]
            );

            if ($dry) {
                $this->line("Would set jid={$r->jid_id} -> insurer_id={$insurer->id} ({$name}) [garage {$r->garage_id}]");
                continue;
            }

            DB::table('job_insurance_details')
                ->where('id', (int) $r->jid_id)
                ->update(['insurer_id' => $insurer->id]);

            $updated++;
        }

        $this->info($dry ? 'Dry-run complete.' : "Backfill complete. Updated {$updated} rows.");
        return self::SUCCESS;
    }
}

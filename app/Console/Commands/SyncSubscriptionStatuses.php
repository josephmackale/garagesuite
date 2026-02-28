<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Garage;

class SyncSubscriptionStatuses extends Command
{
    protected $signature = 'subscriptions:sync-status {--dry-run : Show what would change without saving}';
    protected $description = 'Normalize garage subscription status (trial/active/expired) based on trial_ends_at and subscription_expires_at';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $now = now();
        $changed = 0;

        // Process in chunks to stay safe on big data
        Garage::select('id', 'name', 'status', 'trial_ends_at', 'subscription_expires_at')
            ->orderBy('id')
            ->chunkById(200, function ($garages) use ($now, $dryRun, &$changed) {
                foreach ($garages as $g) {
                    $newStatus = $this->computeStatus($g->trial_ends_at, $g->subscription_expires_at, $now);

                    if (($g->status ?? '') !== $newStatus) {
                        $changed++;

                        $this->line(sprintf(
                            "[%s] #%d %s: %s -> %s (trial=%s, sub=%s)",
                            $dryRun ? 'DRY' : 'SYNC',
                            $g->id,
                            $g->name,
                            $g->status ?? 'null',
                            $newStatus,
                            optional($g->trial_ends_at)->toDateString(),
                            optional($g->subscription_expires_at)->toDateString()
                        ));

                        if (! $dryRun) {
                            $g->status = $newStatus;
                            $g->save();
                        }
                    }
                }
            });

        $this->info("Done. Status changes: {$changed}" . ($dryRun ? " (dry-run)" : ""));

        return self::SUCCESS;
    }

    private function computeStatus($trialEndsAt, $subEndsAt, $now): string
    {
        // Active subscription wins
        if (!empty($subEndsAt) && $subEndsAt->isFuture()) {
            return 'active';
        }

        // Trial if trial date exists and still future
        if (!empty($trialEndsAt) && $trialEndsAt->isFuture()) {
            return 'trial';
        }

        // If either exists and is past, it’s expired
        if (!empty($subEndsAt) || !empty($trialEndsAt)) {
            return 'expired';
        }

        // Brand new / not started (your call)
        return 'inactive';
    }
}

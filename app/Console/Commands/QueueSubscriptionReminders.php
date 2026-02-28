<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class QueueSubscriptionReminders extends Command
{
    protected $signature = 'subscriptions:queue-reminders
                            {--dry-run : Do not write anything to DB}
                            {--include-expired : Also queue reminders for already-expired garages (safe, deduped)}';

    protected $description = 'Queue subscription/trial expiry reminders into sms_logs (pending)';

    public function handle(): int
    {
        $today = now()->startOfDay();

        // ✅ Added 14 days so you start seeing reminders earlier
        $daysList = [14, 7, 3, 1, 0];

        $dryRun = (bool) $this->option('dry-run');
        $includeExpired = (bool) $this->option('include-expired');

        $countQueued = 0;

        // ---------------------------
        // 1) Upcoming expiries
        // ---------------------------
        foreach ($daysList as $daysLeft) {
            $targetDate = $today->copy()->addDays($daysLeft);

            $garages = DB::table('garages')
                ->select('id', 'name', 'phone', 'trial_ends_at', 'subscription_expires_at', 'status')
                ->where(function ($q) use ($targetDate) {
                    $q->whereDate('subscription_expires_at', $targetDate)
                      ->orWhereDate('trial_ends_at', $targetDate);
                })
                ->get();

            foreach ($garages as $g) {
                $countQueued += $this->queueOneGarageReminder($g, $daysLeft, $targetDate->toDateString(), $dryRun);
            }
        }

        // ---------------------------
        // 2) Already expired follow-ups (optional)
        // ---------------------------
        if ($includeExpired) {
            // For expired garages, you can ping on day 1, 3, 7 after expiry (choose what you want)
            $afterExpiryDays = [1, 3, 7];

            foreach ($afterExpiryDays as $daysAfter) {
                $expiryDate = $today->copy()->subDays($daysAfter)->toDateString();

                // garages that expired exactly $daysAfter days ago (trial or subscription)
                $garages = DB::table('garages')
                    ->select('id', 'name', 'phone', 'trial_ends_at', 'subscription_expires_at', 'status')
                    ->where(function ($q) use ($expiryDate) {
                        $q->whereDate('subscription_expires_at', $expiryDate)
                          ->orWhereDate('trial_ends_at', $expiryDate);
                    })
                    ->get();

                foreach ($garages as $g) {
                    $daysLeft = -$daysAfter; // negative means already expired
                    $countQueued += $this->queueOneGarageReminder($g, $daysLeft, $expiryDate, $dryRun);
                }
            }
        }

        $this->info("Queued {$countQueued} reminder(s) into sms_logs.");
        return self::SUCCESS;
    }

    private function queueOneGarageReminder(object $g, int $daysLeft, string $forDate, bool $dryRun): int
    {
        $phone = $g->phone ?? null;
        if (! $phone) {
            return 0;
        }

        $isTrial = !empty($g->trial_ends_at) && (date('Y-m-d', strtotime($g->trial_ends_at)) === $forDate);
        $isSub   = !empty($g->subscription_expires_at) && (date('Y-m-d', strtotime($g->subscription_expires_at)) === $forDate);

        $kind = $isSub ? 'SUB' : 'TRIAL';

        // Deterministic tag for dedupe
        $tag = sprintf('[%s-REMINDER D%d %s]', $kind, $daysLeft, $forDate);

        $message = $this->buildMessage($g->name, $daysLeft, $kind, $tag);

        // Deduplicate: if message tag already exists for this garage+phone, skip
        $exists = DB::table('sms_logs')
            ->where('garage_id', $g->id)
            ->where('phone', $phone)
            ->where('message', 'like', '%'.$tag.'%')
            ->exists();

        if ($exists) {
            return 0;
        }

        if ($dryRun) {
            $this->line("DRY RUN: queue sms_logs garage_id={$g->id} phone={$phone} {$tag}");
            return 0;
        }

        DB::table('sms_logs')->insert([
            'garage_id'     => $g->id,
            'customer_id'   => null,
            'job_id'        => null,
            'phone'         => $phone,
            'message'       => $message,
            'provider'      => 'system',
            'status'        => 'pending',
            'error_message' => null,
            'sent_at'       => null,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        return 1;
    }

    private function buildMessage(string $garageName, int $daysLeft, string $kind, string $tag): string
    {
        $brand = 'GarageSuite';

        if ($daysLeft < 0) {
            $d = abs($daysLeft);
            return "{$brand}: Your account is locked (expired {$d} day(s) ago). Renew now to regain access. {$tag}";
        }

        if ($kind === 'TRIAL') {
            if ($daysLeft === 0) {
                return "{$brand}: Your trial ends today. Subscribe to continue using the system. {$tag}";
            }
            return "{$brand}: Your trial ends in {$daysLeft} day(s). Subscribe early to avoid interruption. {$tag}";
        }

        if ($daysLeft === 0) {
            return "{$brand}: Your subscription expires today. Renew now to avoid being locked out. {$tag}";
        }

        return "{$brand}: Your subscription expires in {$daysLeft} day(s). Renew early to avoid interruption. {$tag}";
    }
}

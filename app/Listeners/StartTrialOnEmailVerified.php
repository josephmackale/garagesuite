<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\DB;

class StartTrialOnEmailVerified
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        DB::transaction(function () use ($user) {
            $garage = $user->garage()
                ->lockForUpdate()
                ->first();

            if (! $garage) {
                return;
            }

            // Start trial only once (don’t overwrite)
            if (empty($garage->subscription_expires_at)) {
                $garage->subscription_expires_at = now()->addDays(7);
            }

            // Activate after verification
            if (($garage->status ?? null) === 'pending') {
                $garage->status = 'active';
            }

            if ($garage->isDirty()) {
                $garage->save();
            }
        });
    }
}

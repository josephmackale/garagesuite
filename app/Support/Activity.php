<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

class Activity
{
    public static function log(string $action, $target = null, array $meta = []): void
    {
        $req = request();

        ActivityLog::create([
            'actor_id'    => Auth::id(),
            'action'      => $action,
            'target_type' => $target ? get_class($target) : null,
            'target_id'   => $target?->id,
            'meta'        => $meta ?: null,
            'ip'          => $req?->ip(),
            'user_agent'  => substr((string) $req?->userAgent(), 0, 1000),
        ]);
    }
}

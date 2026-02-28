<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Auth\Events\Verified;
use App\Listeners\StartTrialOnEmailVerified;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        Verified::class => [
            StartTrialOnEmailVerified::class,
        ],
    ];
}

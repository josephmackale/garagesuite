@php
    use Carbon\Carbon;

    $user = auth()->user();
    $garage = $user?->garage ?? null;

    if (! $garage) return;

    $now = now();

    $subEnds = $garage->subscription_expires_at ? Carbon::parse($garage->subscription_expires_at) : null;
    $trialEnds = $garage->trial_ends_at ? Carbon::parse($garage->trial_ends_at) : null;

    $hasActiveSub = $subEnds && $now->lt($subEnds);
    $hasActiveTrial = $trialEnds && $now->lt($trialEnds);

    if (! $hasActiveSub && ! $hasActiveTrial) {
        // expired users are redirected by EnsureSubscriptionMode
        return;
    }

    $kind = $hasActiveSub ? 'subscription' : 'trial';
    $endsAt = $hasActiveSub ? $subEnds : $trialEnds;

    $daysLeft = $now->copy()->startOfDay()->diffInDays($endsAt->copy()->startOfDay(), false);
    $endsOn = $endsAt->format('d M Y');

    $show = $kind === 'trial'
        ? (in_array($daysLeft, [14,7,3,1,0], true) || $daysLeft <= 7)
        : ($daysLeft <= 7);

    if (! $show) return;

    $billingUrl = \Illuminate\Support\Facades\Route::has('billing.index')
        ? route('billing.index')
        : url('/billing');

    $title = $kind === 'trial' ? 'Trial notice' : 'Subscription notice';

    if ($daysLeft <= 0) {
        $msg = $kind === 'trial'
            ? "Your trial ends today ({$endsOn}). Activate to continue using GarageSuite."
            : "Your subscription expires today ({$endsOn}). Renew now to avoid being locked.";
    } else {
        $msg = $kind === 'trial'
            ? "Your trial ends in {$daysLeft} day(s) (on {$endsOn}). Activate early to avoid interruption."
            : "Your subscription expires in {$daysLeft} day(s) (on {$endsOn}). Renew early to avoid interruption.";
    }
    $routeName = optional(request()->route())->getName();
    if (in_array($routeName, ['billing.index', 'billing.show', 'billing.locked'], true)) {
        return;
    }

@endphp

<div class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-amber-900 shadow-sm">
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="text-sm font-bold">{{ $title }}</div>
            <div class="text-sm">{{ $msg }}</div>
        </div>

        <a href="{{ $billingUrl }}"
           class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
            {{ $kind === 'trial' ? 'Activate' : 'Renew' }}
        </a>
    </div>
</div>

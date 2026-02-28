<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
    @foreach($boardStatuses as $key => $meta)
        @include('jobs.partials.board-column', [
            'statusKey' => $key,
            'meta' => $meta,
            'jobs' => $jobsByStatus[$key],
            'count' => $countFor($key),
            'amount' => $amountFor($key),
        ])
    @endforeach
</div>

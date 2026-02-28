<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AdminActivityController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q'));

        $logs = ActivityLog::query()
            ->with('actor')
            ->when($q !== '', function ($query) use ($q) {
                $query->where('action', 'like', "%{$q}%")
                      ->orWhere('target_type', 'like', "%{$q}%")
                      ->orWhere('target_id', 'like', "%{$q}%");
            })
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.activity.index', compact('logs', 'q'));
    }
}

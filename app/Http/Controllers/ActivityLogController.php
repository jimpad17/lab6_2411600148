<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    /**
     * Read-only audit page: every page visit, search, sign-in and inventory
     * change is recorded by middleware / controllers / auth listeners.
     */
    public function index(Request $request)
    {
        $logs = ActivityLog::with('user')
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%'.strip_tags((string) $request->input('search')).'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('description', 'like', $term)
                        ->orWhere('module', 'like', $term)
                        ->orWhere('action', 'like', $term);
                });
            })
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('module'), fn ($q) => $q->where('module', $request->input('module')))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->input('period') === 'today', fn ($q) => $q->today())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('activity-logs.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'actionOptions' => ['viewed', 'searched', 'created', 'updated', 'deactivated', 'restored', 'logged in', 'logged out'],
            'moduleOptions' => ActivityLog::select('module')->distinct()->orderBy('module')->pluck('module'),
            'totalActions' => ActivityLog::count(),
            'actionsToday' => ActivityLog::today()->count(),
            'activeUsers' => User::whereHas('activities')->count(),
            'modulesCount' => ActivityLog::select('module')->distinct()->count(),
            'filters' => $request->only(['search', 'action', 'module', 'user_id', 'period']),
        ]);
    }
}

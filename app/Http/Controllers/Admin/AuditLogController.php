<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $tab     = $request->query('tab', 'login');
        $section = $tab;

        // Actions actually recorded for this section, to populate the filter.
        $actions = ActivityLog::where('section', $section)
            ->select('action')->distinct()->orderBy('action')->pluck('action');

        $action = $request->query('action');

        $query = ActivityLog::query()
            ->where('section', $section)
            ->when($request->filled('user_search'), function ($q) use ($request) {
                $term = '%' . $request->user_search . '%';
                $q->where(fn ($w) => $w->where('user_name', 'like', $term)->orWhere('user_email', 'like', $term));
            })
            ->when($action && $actions->contains($action), fn ($q) => $q->where('action', $action))
            ->when($request->filled('date_from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date_from))
            ->when($request->filled('date_to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date_to))
            ->latest('created_at');

        $logs = $query->paginate(25)->withQueryString();

        return view('panel.audit-log', compact('tab', 'logs', 'actions', 'action'));
    }
}

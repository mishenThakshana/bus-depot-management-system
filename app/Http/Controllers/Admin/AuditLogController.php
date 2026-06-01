<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LoginLog;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = LoginLog::query()->latest('created_at');

        if ($request->filled('user_search')) {
            $term = '%' . $request->user_search . '%';
            $query->where(function ($q) use ($term) {
                $q->where('user_name', 'like', $term)
                  ->orWhere('user_email', 'like', $term);
            });
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(25)->withQueryString();

        return view('panel.audit-log', compact('logs'));
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $logs = AuditLog::query()
            ->with('user:id,name')
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->input('user_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->input('action')))
            ->when($request->filled('type'), fn ($q) => $q->where('auditable_type', $request->input('type')))
            ->when($request->filled('from'), fn ($q) => $q->whereDate('created_at', '>=', $request->date('from')))
            ->when($request->filled('to'), fn ($q) => $q->whereDate('created_at', '<=', $request->date('to')))
            ->latest('id')
            ->paginate(per_page())
            ->withQueryString();

        return view('audit.index', [
            'logs' => $logs,
            'users' => User::orderBy('name')->get(['id', 'name']),
            'types' => AuditLog::query()->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
        ]);
    }
}

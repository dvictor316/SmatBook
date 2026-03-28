<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::query()->with('user');

        if (!$this->canViewAll($user)) {
            $query->where('user_id', $user?->id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $module = trim((string) $request->input('module', ''));
        if ($module !== '') {
            $query->where('module', $module);
        }

        $logs = $query->latest()->paginate(25)->withQueryString();
        $modules = ActivityLog::query()
            ->select('module')
            ->whereNotNull('module')
            ->when(!$this->canViewAll($user), fn ($q) => $q->where('user_id', $user?->id))
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        return view('activity-log.index', compact('logs', 'modules', 'search', 'module'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function export(Request $request)
    {
        $user = Auth::user();
        $query = ActivityLog::query()->with('user');

        if (!$this->canViewAll($user)) {
            $query->where('user_id', $user?->id);
        }

        $search = trim((string) $request->input('search', ''));
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('action', 'like', "%{$search}%")
                    ->orWhere('module', 'like', "%{$search}%")
                    ->orWhere('ip_address', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $module = trim((string) $request->input('module', ''));
        if ($module !== '') {
            $query->where('module', $module);
        }

        $logs = $query->latest()->get();
        $filename = 'activity_logs_' . now()->format('Y-m-d_His') . '.csv';
        $headers = [
            'Content-type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        $columns = ['Date', 'User', 'Module', 'Action', 'Description', 'IP Address', 'User Agent'];

        $callback = function () use ($logs, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($logs as $log) {
                fputcsv($file, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->user?->name ?? 'System',
                    $log->module ?? 'general',
                    $log->action ?? 'action',
                    Str::of((string) $log->description)->limit(200, '...'),
                    $log->ip_address ?? '',
                    $log->user_agent ?? '',
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function canViewAll($user): bool
    {
        $role = strtolower(trim((string) ($user?->role ?? '')));
        $relatedRole = strtolower(trim((string) optional($user?->role)->name));

        return in_array($role, ['super_admin', 'administrator', 'superadmin'], true)
            || in_array($relatedRole, ['super_admin', 'administrator', 'superadmin'], true)
            || (string) $user?->email === 'donvictorlive@gmail.com';
    }
}

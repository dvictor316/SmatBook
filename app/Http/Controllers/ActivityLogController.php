<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ActivityLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = $this->baseQuery($user);

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

        if ($request->filled('from_date')) {
            $query->whereDate('created_at', '>=', $request->input('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('created_at', '<=', $request->input('to_date'));
        }

        $statsQuery = clone $query;

        $logs = $query->latest()->paginate(25)->withQueryString();
        $modulesQuery = ActivityLog::withoutGlobalScopes()
            ->select('module')
            ->whereNotNull('module');

        if (!$this->canViewAll($user)) {
            $modulesQuery->where('user_id', $user?->id);
            $this->applyTenantBranchScope($modulesQuery, 'activity_logs');
        } else {
            $role = strtolower(trim((string) ($user?->role ?? '')));
            $isCentralSuperAdmin = in_array($role, ['super_admin', 'superadmin'], true)
                || (string) $user?->email === 'donvictorlive@gmail.com';

            if (!$isCentralSuperAdmin) {
                $this->applyTenantBranchScope($modulesQuery, 'activity_logs');
            }
        }

        $modules = $modulesQuery
            ->distinct()
            ->orderBy('module')
            ->pluck('module');

        $stats = [
            'total' => (clone $statsQuery)->count(),
            'today' => (clone $statsQuery)->whereDate('created_at', now()->toDateString())->count(),
            'users' => (clone $statsQuery)->distinct('user_id')->count('user_id'),
            'modules' => (clone $statsQuery)->whereNotNull('module')->distinct('module')->count('module'),
        ];

        return view('activity-log.index', compact('logs', 'modules', 'search', 'module', 'stats'));
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
        $query = $this->baseQuery($user);

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

    private function baseQuery($user)
    {
        $query = ActivityLog::withoutGlobalScopes()->with('user');
        $scope = $this->scopeContext();
        $companyId = $scope['company_id'];
        $branchId = $scope['branch_id'];

        if (!$this->canViewAll($user)) {
            $query->where('user_id', $user?->id);
            return $this->applyTenantBranchScope($query, 'activity_logs');
        }

        $role = strtolower(trim((string) ($user?->role ?? '')));
        $isCentralSuperAdmin = in_array($role, ['super_admin', 'superadmin'], true)
            || (string) $user?->email === 'donvictorlive@gmail.com';

        if (!$isCentralSuperAdmin) {
            if ($companyId > 0 && Schema::hasColumn('activity_logs', 'company_id')) {
                $query->where('company_id', $companyId);
            }

            if ($branchId !== '' && Schema::hasColumn('activity_logs', 'branch_id')) {
                $query->where('branch_id', $branchId);
            }
        }

        return $query;
    }
}

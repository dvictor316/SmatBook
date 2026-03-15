<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use App\Models\Permission;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleController extends Controller
{
    /**
     * Display role management view.
     * domain => env('SESSION_DOMAIN', null)
     */
    public function index()
    {
        $this->ensurePermissionCatalog();

        $roles = Role::withCount('permissions')
            ->orderBy('name', 'asc')
            ->get();

        return view('UserManagement.roles-permission', compact('roles'));
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'description' => 'nullable|string|max:1000',
            'role_group' => 'nullable|string|max:191',
        ]);

        $roleName = Str::title($request->name);

        Role::create([
            'name' => $roleName,
            'description' => $request->description,
            'role_group' => $request->role_group ?: 'Staff',
            'is_system_role' => false,
        ]);

        return redirect()->back()->with('success', "Role '$roleName' has been established.");
    }

    /**
     * Pre-seeding the standard hierarchy for your clients.
     * Note: Added 'Deployment Manager' to the default hierarchy to align with your new feature.
     */
    public function seedDefaultRoles()
    {
        $this->ensurePermissionCatalog();

        $defaultRoles = [
            ['name' => 'Administrator', 'desc' => 'Full system access (Client Owner)', 'group' => 'Executive'],
            ['name' => 'Deployment Manager', 'desc' => 'Can deploy plans and monitor clients', 'group' => 'Partnership'],
            ['name' => 'Finance Manager', 'desc' => 'Manages accounts, taxes, and reports', 'group' => 'Finance'],
            ['name' => 'Store Manager', 'desc' => 'Manages inventory and stock levels', 'group' => 'Operations'],
            ['name' => 'Sales Manager', 'desc' => 'Manages sales teams and targets', 'group' => 'Sales'],
            ['name' => 'Account Officer', 'desc' => 'Handles daily bookkeeping', 'group' => 'Finance'],
            ['name' => 'Cashier', 'desc' => 'Point of Sale operations only', 'group' => 'Sales'],
        ];

        foreach ($defaultRoles as $role) {
            $roleModel = Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['desc'], 'role_group' => $role['group'], 'is_system_role' => true]
            );

            $permissionNames = $this->defaultPermissionsForRole($role['name']);
            if ($permissionNames !== []) {
                $permissionIds = Permission::query()
                    ->whereIn('name', $permissionNames)
                    ->pluck('id')
                    ->all();
                $roleModel->permissions()->syncWithoutDetaching($permissionIds);
            }
        }

        return redirect()->back()->with('success', 'Default company hierarchy deployed.');
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:191|unique:roles,name,' . $id,
            'description' => 'nullable|string|max:1000',
            'role_group' => 'nullable|string|max:191',
        ]);

        $role = Role::findOrFail($id);
        $oldName = $role->name;
        $newName = Str::title($request->name);

        $role->update([
            'name' => $newName,
            'description' => $request->description,
            'role_group' => $request->role_group ?: ($role->role_group ?? 'Staff'),
        ]);

        DB::table('users')
            ->where('role_id', $role->id)
            ->update(['role' => Str::snake(strtolower($newName))]);

        DB::table('users')
            ->whereRaw('LOWER(role) = ?', [strtolower($oldName)])
            ->update([
                'role_id' => $role->id,
                'role' => Str::snake(strtolower($newName)),
            ]);
        
        return redirect()->back()->with('success', 'Role updated successfully');
    }

    /**
     * Remove a role with safety checks.
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        
        // Safety check: Protect core administrative roles
        $protectedRoles = ['Administrator', 'Deployment Manager'];
        if(in_array($role->name, $protectedRoles) || (bool) ($role->is_system_role ?? false)) {
            return redirect()->back()->with('error', 'Cannot delete core system roles');
        }

        DB::table('users')->where('role_id', $role->id)->update(['role_id' => null]);
        if (Schema::hasTable('role_has_permissions')) {
            $role->permissions()->detach();
        }
        
        $role->delete();
        return redirect()->back()->with('success', 'Role deleted successfully');
    }

    /**
     * Show permissions for a specific role.
     */
    public function showPermissions($id)
    {
        $this->ensurePermissionCatalog();

        $role = Role::findOrFail($id);
        $groupedPermissions = $this->permissionGroups();
        $assignedPermissions = $role->permissions()->pluck('permissions.name')->all();

        return view('UserManagement.permission', compact('role', 'groupedPermissions', 'assignedPermissions'));
    }

    /**
     * Update permissions mapping.
     */
    public function updatePermissions(Request $request)
    {
        $validated = $request->validate([
            'role_id' => 'required|exists:roles,id',
            'permissions' => 'nullable|array',
        ]);

        $this->ensurePermissionCatalog();

        $role = Role::findOrFail($validated['role_id']);
        $selectedNames = $this->flattenPermissionPayload((array) ($validated['permissions'] ?? []));

        $permissionIds = Permission::query()
            ->whereIn('name', $selectedNames)
            ->pluck('id')
            ->all();

        $role->permissions()->sync($permissionIds);
        
        return redirect()->back()->with('success', 'Permissions updated for this role!');
    }

    /**
     * Handle user deletion requests from JSON storage.
     */
    public function deleteUserRequest(Request $request)
    {
        $userId = $request->input('user_id');
        $path = public_path('assets/json/delete-account-request.json');

        if (!file_exists($path)) {
            return redirect()->back()->with('error', 'Data file not found at: ' . $path);
        }

        $jsonContent = file_get_contents($path);
        $accounts = json_decode($jsonContent, true) ?? [];

        $updated = array_values(array_filter($accounts, function($a) use ($userId) {
            return $a['Id'] != $userId;
        }));

        file_put_contents($path, json_encode($updated, JSON_PRETTY_PRINT));

        return redirect()->back()->with('success', 'User deleted successfully.');
    }

    private function ensurePermissionCatalog(): void
    {
        if (!Schema::hasTable('permissions')) {
            return;
        }

        foreach ($this->permissionMatrix() as $module => $subModules) {
            foreach ($subModules as $subModule => $actions) {
                foreach ($actions as $action) {
                    Permission::firstOrCreate([
                        'name' => $this->permissionName($module, $subModule, $action),
                    ]);
                }
            }
        }
    }

    private function permissionGroups(): array
    {
        $permissions = Permission::query()->orderBy('name')->pluck('name')->all();
        $grouped = [];

        foreach ($permissions as $permission) {
            $parts = explode('.', $permission);
            $module = Str::headline($parts[0] ?? 'General');
            $subModule = Str::headline($parts[1] ?? 'General');
            $action = $parts[2] ?? 'view';

            $grouped[$module][$subModule][] = $action;
        }

        foreach ($grouped as $module => $subModules) {
            foreach ($subModules as $subModule => $actions) {
                $grouped[$module][$subModule] = collect($actions)
                    ->unique()
                    ->sortBy(fn ($action) => array_search($action, ['view', 'create', 'edit', 'delete'], true))
                    ->values()
                    ->all();
            }
        }

        return $grouped;
    }

    private function permissionMatrix(): array
    {
        return [
            'dashboard' => ['overview' => ['view']],
            'user_management' => ['users' => ['view', 'create', 'edit', 'delete']],
            'roles' => ['permissions' => ['view', 'create', 'edit', 'delete']],
            'customers' => ['customers' => ['view', 'create', 'edit', 'delete']],
            'vendors' => ['vendors' => ['view', 'create', 'edit', 'delete']],
            'inventory' => [
                'products' => ['view', 'create', 'edit', 'delete'],
                'categories' => ['view', 'create', 'edit', 'delete'],
                'stock' => ['view', 'edit'],
            ],
            'sales' => [
                'invoices' => ['view', 'create', 'edit', 'delete'],
                'pos' => ['view', 'create'],
                'quotations' => ['view', 'create', 'edit', 'delete'],
            ],
            'purchases' => [
                'purchases' => ['view', 'create', 'edit', 'delete'],
                'vendors' => ['view'],
            ],
            'finance' => [
                'expenses' => ['view', 'create', 'edit', 'delete'],
                'payments' => ['view', 'create', 'edit'],
                'accounts' => ['view', 'create', 'edit'],
            ],
            'reports' => ['reports' => ['view']],
            'payroll' => ['payroll' => ['view', 'create', 'edit']],
            'tax' => ['filings' => ['view', 'create', 'edit']],
            'settings' => ['settings' => ['view', 'edit']],
            'projects' => ['projects' => ['view', 'create', 'edit', 'delete']],
            'deployment' => ['managers' => ['view', 'create', 'edit']],
        ];
    }

    private function permissionName(string $module, string $subModule, string $action): string
    {
        return Str::snake($module) . '.' . Str::snake($subModule) . '.' . Str::snake($action);
    }

    private function flattenPermissionPayload(array $payload): array
    {
        $selected = [];

        foreach ($payload as $module => $subModules) {
            foreach ((array) $subModules as $subModule => $actions) {
                foreach ((array) $actions as $action => $enabled) {
                    if ((string) $enabled === '1') {
                        $selected[] = $this->permissionName((string) $module, (string) $subModule, (string) $action);
                    }
                }
            }
        }

        return array_values(array_unique($selected));
    }

    private function defaultPermissionsForRole(string $roleName): array
    {
        return match (strtolower($roleName)) {
            'administrator' => Permission::query()->pluck('name')->all(),
            'deployment manager' => [
                'dashboard.overview.view',
                'deployment.managers.view',
                'sales.quotations.view',
                'reports.reports.view',
                'settings.settings.view',
            ],
            'finance manager' => [
                'dashboard.overview.view',
                'finance.expenses.view', 'finance.expenses.create', 'finance.expenses.edit',
                'finance.payments.view', 'finance.payments.create',
                'reports.reports.view',
                'tax.filings.view', 'tax.filings.create',
                'payroll.payroll.view',
            ],
            'store manager' => [
                'dashboard.overview.view',
                'inventory.products.view', 'inventory.products.create', 'inventory.products.edit',
                'inventory.categories.view',
                'inventory.stock.view', 'inventory.stock.edit',
                'purchases.purchases.view', 'purchases.purchases.create', 'purchases.purchases.edit',
                'reports.reports.view',
            ],
            'sales manager' => [
                'dashboard.overview.view',
                'sales.invoices.view', 'sales.invoices.create', 'sales.invoices.edit',
                'sales.pos.view', 'sales.pos.create',
                'sales.quotations.view', 'sales.quotations.create', 'sales.quotations.edit',
                'customers.customers.view',
                'reports.reports.view',
            ],
            'account officer' => [
                'dashboard.overview.view',
                'finance.expenses.view', 'finance.expenses.create',
                'finance.payments.view', 'finance.payments.create',
                'sales.invoices.view',
                'reports.reports.view',
            ],
            'cashier' => [
                'dashboard.overview.view',
                'sales.pos.view', 'sales.pos.create',
                'sales.invoices.view',
                'customers.customers.view',
            ],
            default => [],
        };
    }
}

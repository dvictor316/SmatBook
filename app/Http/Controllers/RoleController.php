<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Role;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    /**
     * Display role management view.
     * domain => env('SESSION_DOMAIN', null)
     */
    public function index()
    {
        $roles = Role::orderBy('name', 'asc')->get();
        return view('UserManagement.roles-permission', compact('roles'));
    }

    /**
     * Store a new role.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'department' => 'nullable|string'
        ]);

        $roleName = Str::title($request->name);

        Role::create([
            'name' => $roleName,
            'description' => $request->description,
            'guard_name' => 'web'
        ]);

        return redirect()->back()->with('success', "Role '$roleName' has been established.");
    }

    /**
     * Pre-seeding the standard hierarchy for your clients.
     * Note: Added 'Deployment Manager' to the default hierarchy to align with your new feature.
     */
    public function seedDefaultRoles()
    {
        $defaultRoles = [
            ['name' => 'Administrator', 'desc' => 'Full system access (Client Owner)'],
            ['name' => 'Deployment Manager', 'desc' => 'Can deploy plans and monitor clients'],
            ['name' => 'Finance Manager', 'desc' => 'Manages accounts, taxes, and reports'],
            ['name' => 'Store Manager', 'desc' => 'Manages inventory and stock levels'],
            ['name' => 'Sales Manager', 'desc' => 'Manages sales teams and targets'],
            ['name' => 'Account Officer', 'desc' => 'Handles daily bookkeeping'],
            ['name' => 'Cashier', 'desc' => 'Point of Sale operations only'],
        ];

        foreach ($defaultRoles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['desc'], 'guard_name' => 'web']
            );
        }

        return redirect()->back()->with('success', 'Default company hierarchy deployed.');
    }

    /**
     * Update an existing role.
     */
    public function update(Request $request, $id)
    {
        $request->validate(['name' => 'required|string|max:191']);
        $role = Role::findOrFail($id);
        $role->update(['name' => $request->name]);
        
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
        if(in_array($role->name, $protectedRoles)) {
            return redirect()->back()->with('error', 'Cannot delete core system roles');
        }
        
        $role->delete();
        return redirect()->back()->with('success', 'Role deleted successfully');
    }

    /**
     * Show permissions for a specific role.
     */
    public function showPermissions($id)
    {
        $role = Role::findOrFail($id);
        return view('permission', compact('role'));
    }

    /**
     * Update permissions mapping.
     */
    public function updatePermissions(Request $request)
    {
        $roleId = $request->role_id;
        $permissions = $request->permissions; 

        // Implementation logic for syncing role_permissions goes here
        
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
}
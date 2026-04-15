<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\User;
use App\Models\DeploymentManager;
use App\Models\ActivityLog;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class UserController extends Controller
{
    private function scopedUsersQuery()
    {
        $query = User::query();
        $actor = Auth::user();

        if ($this->isCentralAdmin($actor)) {
            return $query;
        }

        $companyId = (int) ($actor?->company_id ?? session('current_tenant_id') ?? 0);
        $userId = (int) ($actor?->id ?? 0);

        if ($companyId > 0 && Schema::hasColumn('users', 'company_id')) {
            $query->where(function ($sub) use ($companyId, $userId) {
                $sub->where('company_id', $companyId);

                if ($userId > 0 && Schema::hasColumn('users', 'user_id')) {
                    $sub->orWhere(function ($fallback) use ($userId) {
                        $fallback->whereNull('company_id')
                            ->where('user_id', $userId);
                    });
                }
            });
        } elseif ($userId > 0 && Schema::hasColumn('users', 'user_id')) {
            $query->where('user_id', $userId);
        } elseif ($userId > 0) {
            $query->where('id', $userId);
        }

        return $query;
    }

    private function findScopedUser($id): User
    {
        return $this->scopedUsersQuery()->findOrFail($id);
    }

    /**
     * Display a listing of users FOR SUPER ADMIN
     */
    public function userIndex()
    {
        $users = $this->scopedUsersQuery()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $roles = $this->getRoles();

        // Tenant-based username suffix = company_id of the logged-in user
        $actor = Auth::user();
        $companySuffix = $actor?->company_id ?? '';

        // Branches for access locations checkboxes
        $branches = collect(json_decode(
            \App\Models\Setting::where('company_id', $actor?->company_id)
                ->where('key', 'branches_json')
                ->value('value') ?? '[]',
            true
        ) ?: [])->filter(fn($b) => !empty($b['id']) && !empty($b['name']))->values();

        return view('UserManagement.users', compact('users', 'roles', 'companySuffix', 'branches'));
    }

    /**
     * Display a listing of users (alias)
     */
    public function index()
    {
        return $this->userIndex();
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        $roles = $this->getRoles();
        return view('UserManagement.create', compact('roles'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name'     => 'required|string|max:255',
            'last_name'      => 'nullable|string|max:255',
            'email'          => 'required|email|unique:users,email',
            'role'           => 'required',
            'password'       => 'required|min:6|same:confirm_password',
            'confirm_password' => 'required',
            'username_base'  => 'nullable|string|max:100|alpha_dash',
            'profile_photo'  => 'nullable|file|mimetypes:image/*|max:2048',
        ]);

        $actor = Auth::user();
        $companyId = $actor?->company_id;

        if ($companyId && !$this->isCentralAdmin($actor)) {
            $subscription = Subscription::resolveCurrentForUser($actor);
            $userLimit = $subscription?->resolvedUserLimit();
            $currentUsers = User::query()
                ->where('company_id', $companyId)
                ->count();

            if ($userLimit !== null && $currentUsers >= $userLimit) {
                $planLabel = $subscription ? $subscription->planLabel() : 'current';

                return redirect()->back()
                    ->withInput()
                    ->with('error', "Your {$planLabel} plan allows {$userLimit} users only. Renew or upgrade to add more users.");
            }
        }

        $user = new User();
        $user->name = trim($request->first_name . ' ' . $request->last_name);
        $user->email = $request->email;
        $selectedRole = $this->resolveSelectedRole($request->role);

        $user->role = $selectedRole['legacy'];
        $user->role_id = $selectedRole['id'];
        $user->password = Hash::make($request->password);
        $user->status = $request->boolean('is_active', true) ? 'active' : 'inactive';
        $user->is_verified = 1;
        $user->allow_login = $request->boolean('allow_login', true) ? 1 : 0;
        $user->company_id = $this->isCentralAdmin($actor) ? null : $companyId;

        // Tenant-based username: base-companyId (e.g. "bestserve-13213")
        if ($request->filled('username_base')) {
            $baseUsername = Str::slug($request->username_base, '');
        } else {
            $baseUsername = Str::slug($request->first_name, '');
        }
        $suffix = $companyId ? '-' . $companyId : '';
        $candidate = $baseUsername . $suffix;
        // Ensure uniqueness
        $counter = 1;
        while (User::where('username', $candidate)->exists()) {
            $candidate = $baseUsername . $counter . $suffix;
            $counter++;
        }
        $user->username = $candidate;

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->save();

        ActivityLog::record('users', 'create_user', 'Created user ' . $user->name, [
            'user_id' => Auth::id(),
            'properties' => [
                'created_user_id' => $user->id,
                'created_user_email' => $user->email,
                'created_user_role' => $user->role,
            ],
        ]);
        
        return redirect()->route($this->usersIndexRoute())
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing a user
     */
    public function edit($id)
    {
        $user = $this->findScopedUser($id);
        $roles = $this->getRoles();
        return view('UserManagement.edit', compact('user', 'roles'));
    }

    /**
     * Display a single user
     */
    public function show($id)
    {
        $user = $this->findScopedUser($id);
        return view('UserManagement.show', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = $this->findScopedUser($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required',
            'profile_photo' => 'nullable|file|mimetypes:image/*|max:2048'
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $selectedRole = $this->resolveSelectedRole($request->role);

        $user->role = $selectedRole['legacy'];
        $user->role_id = $selectedRole['id'];

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->save();

        ActivityLog::record('users', 'update_user', 'Updated user ' . $user->name, [
            'user_id' => Auth::id(),
            'properties' => [
                'updated_user_id' => $user->id,
                'updated_user_email' => $user->email,
                'updated_user_role' => $user->role,
            ],
        ]);
        
        return redirect()->route($this->usersIndexRoute())
            ->with('success', 'User updated successfully!');
    }

    /**
     * Update user's plan
     */
    public function updatePlan(Request $request, $id)
    {
        $user = User::findOrFail($id);
        
        $request->validate([
            'plan' => 'required|in:Basic,Professional,Enterprise'
        ]);

        if ($user->company) {
            $user->company->update(['plan' => $request->plan]);
        }

        return redirect()->back()->with('success', 'User plan updated successfully!');
    }

    /**
     * Remove the specified user
     */
    public function destroy($id)
    {
        $user = $this->findScopedUser($id);

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

        ActivityLog::record('users', 'delete_user', 'Deleted user ' . $user->name, [
            'user_id' => Auth::id(),
            'properties' => [
                'deleted_user_id' => $user->id,
                'deleted_user_email' => $user->email,
            ],
        ]);

        $user->delete();
        
        return redirect()->route($this->usersIndexRoute())
            ->with('success', 'User deleted successfully.');
    }

    /**
     * ACTIVATE A USER - THIS IS WHAT THE "APPROVE" BUTTON CALLS
     */
    public function activate($id)
    {
        DB::beginTransaction();
        
        try {
            // Find the deployment manager record
            $deploymentManager = DeploymentManager::findOrFail($id);
            
            // Update deployment manager status
            $deploymentManager->update([
                'status' => 'active'
            ]);

            // Update the associated user
            if ($deploymentManager->user) {
                $deploymentManager->user->update([
                    'status' => 'active',
                    'is_verified' => 1,
                    'email_verified_at' => now()
                ]);

                ActivityLog::record('users', 'activate_user', 'Activated user ' . $deploymentManager->user->name, [
                    'user_id' => Auth::id(),
                    'company_id' => $deploymentManager->user->company_id,
                    'properties' => [
                        'activated_user_id' => $deploymentManager->user->id,
                        'deployment_manager_id' => $deploymentManager->id,
                    ],
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'User activated and approved successfully!');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with('error', 'Failed to activate user: ' . $e->getMessage());
        }
    }

    /**
     * DEACTIVATE A USER
     */
    public function deactivate($id)
    {
        DB::beginTransaction();
        
        try {
            $deploymentManager = DeploymentManager::findOrFail($id);
            
            $deploymentManager->update([
                'status' => 'inactive'
            ]);

            if ($deploymentManager->user) {
                $deploymentManager->user->update([
                    'status' => 'inactive'
                ]);

                ActivityLog::record('users', 'deactivate_user', 'Deactivated user ' . $deploymentManager->user->name, [
                    'user_id' => Auth::id(),
                    'company_id' => $deploymentManager->user->company_id,
                    'properties' => [
                        'deactivated_user_id' => $deploymentManager->user->id,
                        'deployment_manager_id' => $deploymentManager->id,
                    ],
                ]);
            }

            DB::commit();

            return redirect()->back()->with('success', 'User has been deactivated successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return redirect()->back()->with('error', 'Failed to deactivate user: ' . $e->getMessage());
        }
    }

    /**
     * Export users list
     */
    public function exportUsers()
    {
        $users = $this->scopedUsersQuery()->orderBy('created_at', 'desc')->get();
        
        $filename = 'users_export_' . date('Y-m-d') . '.csv';
        
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $columns = ['ID', 'Name', 'Email', 'Role', 'Status', 'Verified', 'Created At'];

        $callback = function() use($users, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->role,
                    $user->status ?? 'active',
                    $user->is_verified ? 'Yes' : 'No',
                    $user->created_at,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Display activity log for a user
     */
    public function activityLog($id)
    {
        $user = $this->findScopedUser($id);
        return view('UserManagement.activity', compact('user'));
    }

    /**
     * Handle Profile Image Updates (Avatar & Cover)
     */
    public function updateProfileVisuals(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'profile_photo' => 'nullable|file|mimetypes:image/*|max:2048',
            'cover_photo'   => 'nullable|file|mimetypes:image/*|max:5120'
        ]);

        if ($request->hasFile('profile_photo')) {
            if ($user->profile_photo) {
                Storage::disk('public')->delete($user->profile_photo);
            }
            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($user->cover_photo) {
                Storage::disk('public')->delete($user->cover_photo);
            }
            $user->cover_photo = $request->file('cover_photo')->store('covers', 'public');
        }

        $user->save();
        return redirect()->back()->with('success', 'Profile visuals updated!');
    }

    /**
     * Display the Real-time Map Dashboard
     */
    public function showRealtimeMap()
    {
        $messages = [
            [
                'Name' => 'Alexander Brooke',
                'Content' => 'New invoice generated for order #8821',
                'Time' => '2 mins ago',
                'Class' => 'unread',
                'Status' => 'online',
                'lat' => 6.5244,
                'lng' => 3.3792,
                'HasAttachment' => true
            ],
            [
                'Name' => 'Sophia Chen',
                'Content' => 'Payment received via Stripe Connect',
                'Time' => '1 hour ago',
                'Class' => 'read',
                'Status' => 'offline',
                'lat' => 40.7128,
                'lng' => -74.0060,
                'HasAttachment' => false
            ],
            [
                'Name' => 'Marcus Hertz',
                'Content' => 'System Alert: Storage limit reaching 90%',
                'Time' => 'Just now',
                'Class' => 'unread',
                'Status' => 'online',
                'lat' => 52.5200,
                'lng' => 13.4050,
                'HasAttachment' => false
            ]
        ];

        return view('realtime-map-inbox', compact('messages'));
    }

    /**
     * Helper: Get available roles
     */
    private function getRoles()
    {
        if (Schema::hasTable('roles')) {
            $rolesFromTable = Role::query()
                ->orderBy('name')
                ->pluck('name')
                ->filter()
                ->values()
                ->all();

            if (!empty($rolesFromTable)) {
                return $rolesFromTable;
            }
        }

        $results = DB::select("SHOW COLUMNS FROM users WHERE Field = 'role'");
        $roles = [];
        
        if (!empty($results)) {
            $type = $results[0]->Type;
            preg_match('/^enum\((.*)\)$/', $type, $matches);
            if (isset($matches[1])) {
                foreach (explode(',', $matches[1]) as $value) {
                    $roles[] = trim($value, "'");
                }
            }
        }
        
        if(empty($roles)) {
            $roles = ['super_admin', 'administrator', 'deployment_manager', 'store_manager', 'accountant', 'cashier'];
        }

        return $roles;
    }

    private function resolveRoleId(?string $roleName): ?int
    {
        return $this->resolveSelectedRole($roleName)['id'];
    }

    private function resolveSelectedRole(?string $roleName): array
    {
        $roleName = trim((string) $roleName);

        if ($roleName === '') {
            return ['id' => null, 'legacy' => null, 'display' => null];
        }

        if (!Schema::hasTable('roles')) {
            return [
                'id' => null,
                'legacy' => $this->legacyRoleKey($roleName),
                'display' => Str::title(str_replace('_', ' ', $roleName)),
            ];
        }

        $role = Role::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($roleName)])
            ->orWhereRaw('LOWER(name) = ?', [strtolower(Str::title(str_replace('_', ' ', $roleName)))])
            ->first();

        if (!$role) {
            $legacyKey = $this->legacyRoleKey($roleName);
            $role = Role::query()
                ->get()
                ->first(fn (Role $candidate) => $this->legacyRoleKey($candidate->name) === $legacyKey);
        }

        if (!$role) {
            return [
                'id' => null,
                'legacy' => $this->legacyRoleKey($roleName),
                'display' => Str::title(str_replace('_', ' ', $roleName)),
            ];
        }

        return [
            'id' => $role->id,
            'legacy' => $this->legacyRoleKey($role->name),
            'display' => $role->name,
        ];
    }

    private function legacyRoleKey(?string $roleName): ?string
    {
        $normalized = strtolower(trim((string) $roleName));

        if ($normalized === '') {
            return null;
        }

        return match ($normalized) {
            'super admin', 'super_admin', 'superadmin' => 'super_admin',
            'administrator', 'admin' => 'administrator',
            'deployment manager', 'deployment_manager', 'manager' => 'deployment_manager',
            'store manager', 'store_manager' => 'store_manager',
            'sales manager', 'sales_manager' => 'sales_manager',
            'finance manager', 'finance_manager' => 'finance_manager',
            'account officer', 'account_officer', 'accountant' => 'accountant',
            default => Str::snake(str_replace('-', ' ', $normalized)),
        };
    }

    private function usersIndexRoute(): string
    {
        if (app('router')->has('super_admin.users.index')) {
            return 'super_admin.users.index';
        }

        return 'users.index';
    }

    private function isCentralAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return in_array(strtolower((string) $user->role), ['super_admin', 'superadmin'], true)
            || strtolower((string) $user->email) === 'donvictorlive@gmail.com';
    }
}

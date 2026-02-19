<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DeploymentManager;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of users FOR SUPER ADMIN
     */
    public function userIndex()
    {
        $users = User::query()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $roles = $this->getRoles();

        return view('UserManagement.users', compact('users', 'roles'));
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
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'role'     => 'required',
            'password' => 'required|min:6',
            'profile_photo' => 'nullable|image|max:2048'
        ]);

        $user = new User();
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;
        $user->password = Hash::make($request->password);
        $user->status = 'active';
        $user->is_verified = 1;

        if ($request->hasFile('profile_photo')) {
            $user->profile_photo = $request->file('profile_photo')->store('profiles', 'public');
        }

        $user->save();
        
        return redirect()->route($this->usersIndexRoute())
            ->with('success', 'User created successfully.');
    }

    /**
     * Show the form for editing a user
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = $this->getRoles();
        return view('UserManagement.edit', compact('user', 'roles'));
    }

    /**
     * Display a single user
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('UserManagement.show', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role'  => 'required',
            'profile_photo' => 'nullable|image|max:2048'
        ]);
        
        $user->name = $request->name;
        $user->email = $request->email;
        $user->role = $request->role;

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
        $user = User::findOrFail($id);

        if ($user->profile_photo) {
            Storage::disk('public')->delete($user->profile_photo);
        }

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
        $users = User::orderBy('created_at', 'desc')->get();
        
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
        $user = User::findOrFail($id);
        return view('UserManagement.activity', compact('user'));
    }

    /**
     * Handle Profile Image Updates (Avatar & Cover)
     */
    public function updateProfileVisuals(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'profile_photo' => 'nullable|image|max:2048',
            'cover_photo'   => 'nullable|image|max:5120'
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

    private function usersIndexRoute(): string
    {
        if (app('router')->has('super_admin.users.index')) {
            return 'super_admin.users.index';
        }

        return 'users.index';
    }
}

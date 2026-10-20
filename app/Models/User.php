<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Support\RoleNames;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        // Auth & Identity
        'name',
        'username',
        'email',
        'password',
        'role_id',
        'role', // Consider deprecating this if 'role_id' exists to normalize data
        'is_protected_super_admin',
        'internal_test_access_enabled',
        'internal_test_access_expires_at',
        'allow_login',
        
        // Profile & Media
        'profile_photo',  
        'cover_photo',    
        'avatar', // Legacy filename support
        'bio',            
        'phone',         
        'location',
        'country',
        'state_region',
        'local_council',
        'company_id',
        'state_manager_id',
        'status',
        'wallet_balance',
        'email_verified_at',
        
        // Social / OAuth
        'google_id',     
        'facebook_id',    
        'linkedin_id',
        'provider_id',    
        'provider_name',
        
        // Status & Verification
        'last_seen',     
        'is_verified',    
        'verified_at',    
        'verified_by',

        // Per-user permission overrides
        'permissions_override',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'two_factor_last_used_timestep',
    ];

    /**
     * The attributes hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'facebook_id',
        'linkedin_id',
        'provider_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at'       => 'datetime',
        'last_seen'         => 'datetime',
        'password'          => 'hashed',
        'is_verified'          => 'boolean',
        'role_id'              => 'integer',
        'company_id'           => 'integer',
        'wallet_balance'       => 'decimal:2',
        'permissions_override' => 'array',
        'is_protected_super_admin' => 'boolean',
        'internal_test_access_enabled' => 'boolean',
        'internal_test_access_expires_at' => 'datetime',
        'two_factor_secret' => 'encrypted',
        'two_factor_recovery_codes' => 'array',
        'two_factor_confirmed_at' => 'datetime',
        'two_factor_last_used_timestep' => 'integer',
    ];

    /* =========================================================================
     * 1. ACCESSORS & MUTATORS (Modern Syntax)
     * ========================================================================= */

    /**
     * Get the user's display avatar.
     * Prioritizes: Uploaded Photo -> External URL -> Legacy Avatar -> UI Avatars
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::make(
            get: function (): string {
                // 1. Check for valid URL in profile_photo
                if ($this->profile_photo && filter_var($this->profile_photo, FILTER_VALIDATE_URL)) {
                    return $this->profile_photo;
                }

                // 2. Check Storage for profile_photo
                if ($profilePhotoUrl = $this->resolveStoredMediaUrl($this->profile_photo)) {
                    return $profilePhotoUrl;
                }

                // 3. Check legacy local asset 'avatar' column
                if ($this->avatar && file_exists(public_path('assets/img/profiles/' . $this->avatar))) {
                    return asset('assets/img/profiles/' . $this->avatar);
                }

                // 4. Generate Deterministic UI Avatar
                return $this->generateUiAvatar();
            }
        );
    }

    protected function profilePhotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->resolveStoredMediaUrl($this->profile_photo) ?: $this->avatar_url
        );
    }

    protected function coverPhotoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->resolveStoredMediaUrl($this->cover_photo) ?: asset('assets/img/profiles/avatar-02.jpg')
        );
    }

    /**
     * Determine if the user is currently "Online" (active in last 5 mins).
     */
    protected function isOnline(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->last_seen && $this->last_seen->diffInMinutes(now()) < 5
        );
    }

    /**
     * Get a human-readable string for last seen status.
     */
    protected function lastSeenHuman(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->last_seen) return 'Never';
                return $this->is_online ? 'Online' : $this->last_seen->diffForHumans();
            }
        );
    }

    /* =========================================================================
     * 2. RELATIONSHIPS - SYSTEM
     * ========================================================================= */

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function roleModel(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'role_id');
    }

    public function assignedRole(): ?Role
    {
        if (!$this->role_id) {
            return null;
        }
        foreach (['roleModel', 'role'] as $relation) {
            if ($this->relationLoaded($relation)) {
                $role = $this->getRelation($relation);
                if ($role instanceof Role && (int) $role->id === (int) $this->role_id) {
                    return $role;
                }
            }
        }
        $role = $this->roleModel()->first();
        $this->setRelation('roleModel', $role);
        return $role;
    }

    protected function roleDisplayName(): Attribute
    {
        return Attribute::make(get: fn () => $this->assignedRole()?->name
            ?? RoleNames::find($this->attributes['role'] ?? null)?->name
            ?? Str::title(str_replace('_', ' ', (string) ($this->attributes['role'] ?? 'User'))));
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class, 'user_id')->latestOfMany('id');
    }

    /* =========================================================================
     * 3. RELATIONSHIPS - BUSINESS
     * ========================================================================= */

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function ownedCompany(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }

    public function companies(): HasMany
    {
        return $this->hasMany(Company::class);
    }

    public function tenant(): HasOne
    {
        return $this->hasOne(Tenant::class, 'user_id');
    }

    public function domain(): HasOne
    {
        // Note: Check if domain belongs to user directly or via tenant
        return $this->hasOne(Domain::class, 'tenant_id', 'id');
    }

    public function deploymentProfile(): HasOne
    {
        return $this->hasOne(DeploymentManager::class, 'user_id');
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }

    /* =========================================================================
     * 4. RELATIONSHIPS - COMMUNICATION (Chat & Email)
     * ========================================================================= */

    public function inbox(): HasMany
    {
        return $this->hasMany(Message::class, 'receiver_id')->where('type', 'email')->latest();
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_id')->where('type', 'email')->latest();
    }

    /* =========================================================================
     * 5. HELPER METHODS & LOGIC
     * ========================================================================= */

    public function hasRole(string $roleName): bool
    {
        if (RoleNames::key($roleName) === null) {
            return false;
        }
        $assignedRole = $this->assignedRole();
        if ($this->role_id) {
            return $assignedRole && RoleNames::key($assignedRole->name) === RoleNames::key($roleName);
        }
        return RoleNames::key($this->attributes['role'] ?? null) === RoleNames::key($roleName);
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function canManageRoles(): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $companyId = (int) (session('current_tenant_id') ?: $this->company_id);
        if ($companyId <= 0) {
            return false;
        }

        return Company::withoutGlobalScope('tenant')->whereKey($companyId)
            ->where(function ($query) {
                $query->where('user_id', $this->id)->orWhere('owner_id', $this->id);
            })->exists();
    }

    public function isDemoUser(): bool
    {
        if ($this->relationLoaded('company') && $this->company) {
            return $this->company->isDemo();
        }

        return $this->company()->exists() && (bool) $this->company()->value('is_demo');
    }

    public function demoCompany(): ?Company
    {
        return $this->company;
    }

    public function isProtectedSuperAdmin(): bool
    {
        return $this->isSuperAdmin() && (bool) ($this->is_protected_super_admin ?? false);
    }

    public function hasTwoFactorAuthenticationEnabled(): bool
    {
        return ! empty($this->two_factor_secret) && $this->two_factor_confirmed_at !== null;
    }

    public function hasPermissionTo(string $permissionName): bool
    {
        $permissionName = strtolower(trim($permissionName));

        // Per-user override takes full precedence over role permissions
        if ($this->permissions_override !== null) {
            return in_array($permissionName, array_map('strtolower', $this->permissions_override), true);
        }

        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return false;
        }


        $role = $this->role_id ? $this->assignedRole() : RoleNames::find($this->attributes['role'] ?? null);
        return $role && $role->permissions()->whereRaw('LOWER(name) = ?', [$permissionName])->exists();
    }

    /**
     * Generate a deterministic local avatar fallback.
     */
    private function generateUiAvatar(): string
    {
        $fallbacks = [
            'avatar-01.jpg',
            'avatar-02.jpg',
            'avatar-03.jpg',
            'avatar-04.jpg',
            'avatar-05.jpg',
            'avatar-06.jpg',
            'avatar-07.jpg',
            'avatar-08.jpg',
            'avatar-09.jpg',
            'avatar-10.jpg',
            'avatar-11.jpg',
            'avatar-12.jpg',
        ];

        $name = trim((string) ($this->name ?? 'SmartProbook User'));
        $index = abs(crc32(strtolower($name))) % count($fallbacks);

        return asset('assets/img/profiles/' . $fallbacks[$index]);
    }

    private function resolveStoredMediaUrl(?string $path): ?string
    {
        $path = trim((string) $path);

        if ($path === '') {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        if (Str::startsWith($path, ['assets/', '/assets/'])) {
            return asset(ltrim($path, '/'));
        }

        $normalizedPath = str_replace('\\', '/', ltrim($path, '/'));
        foreach (['public/', 'storage/', 'public/storage/'] as $prefix) {
            if (Str::startsWith($normalizedPath, $prefix)) {
                $normalizedPath = ltrim(substr($normalizedPath, strlen($prefix)), '/');
            }
        }

        if ($normalizedPath && Storage::disk('public')->exists($normalizedPath)) {
            return route('media.public', ['path' => $normalizedPath]);
        }

        if (Str::startsWith(ltrim($path, '/'), 'storage/')) {
            return asset(ltrim($path, '/'));
        }

        if ($normalizedPath && file_exists(public_path('storage/' . $normalizedPath))) {
            return asset('storage/' . $normalizedPath);
        }

        if (file_exists(public_path(ltrim($path, '/')))) {
            return asset(ltrim($path, '/'));
        }

        return null;
    }

    /* =========================================================================
     * 6. CHAT LOGIC (Optimized)
     * ========================================================================= */

    /**
     * Get unique users this user has chatted with.
     */
    public function chatContacts()
    {
        // Union query for better performance than distinct subqueries in PHP
        $sent = Chat::select('receiver_id as id')->where('sender_id', $this->id);
        $received = Chat::select('sender_id as id')->where('receiver_id', $this->id);

        $contactIds = $sent->union($received)->pluck('id');

        return User::whereIn('id', $contactIds)->orderBy('name')->get();
    }

    public function unreadMessagesFrom($userId): int
    {
        return Chat::where('sender_id', $userId)
            ->where('receiver_id', $this->id)
            ->where(function ($q) {
                // Laravel 9+ syntax for JSON "where meta->read != true"
                $q->whereNull('meta')
                  ->orWhere('meta->read', '!=', 'true');
            })
            ->count();
    }

    public function totalUnreadMessages(): int
    {
        return Chat::where('receiver_id', $this->id)
            ->where(function ($q) {
                $q->whereNull('meta')
                  ->orWhere('meta->read', '!=', 'true');
            })
            ->count();
    }

    public function lastMessageWith($userId): ?Model
    {
        return Chat::where(fn($q) => $q->where('sender_id', $this->id)->where('receiver_id', $userId))
            ->orWhere(fn($q) => $q->where('sender_id', $userId)->where('receiver_id', $this->id))
            ->latest()
            ->first();
    }

    /* =========================================================================
     * 7. EMAIL STATS LOGIC
     * ========================================================================= */

    public function unreadCount(): int
    {
        return $this->inbox()->whereNull('read_at')->count();
    }

    public function sentCount(): int
    {
        return $this->sentMessages()->count();
    }

    public function trashCount(): int
    {
        return Message::onlyTrashed()
            ->where(fn($q) => $q->where('sender_id', $this->id)->orWhere('receiver_id', $this->id))
            ->where('type', 'email')
            ->count();
    }
}

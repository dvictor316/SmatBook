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
        'allow_login',
        
        // Profile & Media
        'profile_photo',  
        'cover_photo',    
        'avatar', // Legacy filename support
        'bio',            
        'phone',         
        'location',
        'company_id',
        'status',
        'email_verified_at',
        
        // Social / OAuth
        'google_id',     
        'facebook_id',    
        'provider_id',    
        'provider_name',
        
        // Status & Verification
        'last_seen',     
        'is_verified',    
        'verified_at',    
        'verified_by',    
    ];

    /**
     * The attributes hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
        'google_id',
        'facebook_id',
        'provider_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'verified_at'       => 'datetime',
        'last_seen'         => 'datetime',
        'password'          => 'hashed',
        'is_verified'       => 'boolean', // Casts 1/0 to true/false automatically
        'role_id'           => 'integer',
        'company_id'        => 'integer',
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
        return $this->belongsTo(Role::class);
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
        $target = $this->normalizeRoleKey($roleName);

        if ($this->relationLoaded('role') && $this->getRelation('role')) {
            return $this->normalizeRoleKey($this->role?->name) === $target;
        }

        if (!empty($this->role_id) && $this->role()->exists()) {
            return $this->normalizeRoleKey((string) $this->role()->value('name')) === $target;
        }

        return $this->normalizeRoleKey((string) ($this->attributes['role'] ?? '')) === $target;
    }

    public function hasPermissionTo(string $permissionName): bool
    {
        if (!Schema::hasTable('permissions') || !Schema::hasTable('role_has_permissions')) {
            return false;
        }

        $permissionName = strtolower(trim($permissionName));

        if (!empty($this->role_id)) {
            return $this->role()
                ->whereHas('permissions', fn ($query) => $query->whereRaw('LOWER(name) = ?', [$permissionName]))
                ->exists();
        }

        if (!empty($this->attributes['role'])) {
            $legacyRole = $this->normalizeRoleKey((string) $this->attributes['role']);

            return Role::query()
                ->where(function ($query) use ($legacyRole) {
                    $query->whereRaw('LOWER(name) = ?', [strtolower((string) $this->attributes['role'])]);
                    if ($legacyRole !== null) {
                        $query->orWhereRaw('LOWER(name) = ?', [strtolower(Str::title(str_replace('_', ' ', $legacyRole)))]);
                    }
                })
                ->whereHas('permissions', fn ($query) => $query->whereRaw('LOWER(name) = ?', [$permissionName]))
                ->exists();
        }

        return false;
    }

    private function normalizeRoleKey(?string $role): ?string
    {
        $normalized = strtolower(trim((string) $role));

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

        $normalizedPath = preg_replace('#^storage/#', '', ltrim($path, '/'));

        if ($normalizedPath && Storage::disk('public')->exists($normalizedPath)) {
            return route('media.public', ['path' => $normalizedPath]);
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

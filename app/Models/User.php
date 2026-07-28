<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Models\License;
use Illuminate\Support\Facades\Storage;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use App\Models\BlogPost;

#[Fillable([
    'name',
    'username',
    'email',
    'password',
    'is_disabled',
    'author_title',
    'author_bio',
    'author_website_url',
    'avatar_path',
])]

#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'is_disabled' => 'boolean',
        ];
    }

    protected $appends = [
        'avatar_url',
    ];

    /**
     * Roles assigned directly to this user.
     */
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * Permissions assigned directly to this user.
     */
    public function advertiserMemberships(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AdvertiserMembership::class);
    }

    public function advertisers(): BelongsToMany
    {
        return $this->belongsToMany(Advertiser::class, 'advertiser_memberships')
            ->withPivot(['role', 'is_primary', 'is_active', 'invited_at', 'accepted_at'])
            ->withTimestamps();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Check if the user has a specific role.
     */
    public function hasRole(string $role): bool
    {
        return $this->roles()
            ->where('name', $role)
            ->exists();
    }

    /**
     * Check if the user has at least one of the given roles.
     */
    public function hasAnyRole(array $roles): bool
    {
        return $this->roles()
            ->whereIn('name', $roles)
            ->exists();
    }

    /**
     * Check if the user has a permission directly or through a role.
     */
    public function hasPermission(string $permission): bool
    {
        if ($this->permissions()
            ->where('name', $permission)
            ->exists()
        ) {
            return true;
        }

        return $this->roles()
            ->whereHas('permissions', function ($query) use ($permission) {
                $query->where('name', $permission);
            })
            ->exists();
    }

    /**
     * Get all permission names from direct permissions and role permissions.
     */
    public function allPermissionNames(): array
    {
        $directPermissions = $this->permissions()
            ->pluck('name')
            ->toArray();

        $rolePermissions = Permission::query()
            ->whereHas('roles.users', function ($query) {
                $query->where('users.id', $this->id);
            })
            ->pluck('name')
            ->toArray();

        return collect($directPermissions)
            ->merge($rolePermissions)
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Get all role names assigned to the user.
     */
    public function roleNames(): array
    {
        return $this->roles()
            ->pluck('name')
            ->toArray();
    }

    public function getActivityName(): string
    {
        return $this->name;
    }

    public function imageFavorites(): HasMany
    {
        return $this->hasMany(ImageFavorite::class);
    }

    public function licenses(): HasMany
    {
        return $this->hasMany(License::class);
    }

    public function downloads(): HasMany
    {
        return $this->hasMany(Download::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function securityEvents(): HasMany
    {
        return $this->hasMany(AccountSecurityEvent::class);
    }

    public function privacyPreference(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPrivacyPreference::class);
    }

    public function notificationPreferences(): HasMany
    {
        return $this->hasMany(NotificationPreference::class);
    }

    public function publicPages(): HasMany
    {
        return $this->hasMany(PublicPage::class, 'created_by_user_id');
    }

    public function blogPosts(): HasMany
    {
        return $this->hasMany(BlogPost::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class);
    }

    public function assignedSupportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_user_id');
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar_path
            ? Storage::disk('public')->url($this->avatar_path)
            : null;
    }



    public function recentlyViewedAssets(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(RecentlyViewedAsset::class);
    }

    public function assetFavorites(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(AssetFavorite::class);
    }

    public function wishLists(): HasMany
    {
        return $this->hasMany(WishList::class)->orderBy('sort_order')->orderBy('name');
    }

    public function assetAffinities(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(UserAssetAffinity::class);
    }
}

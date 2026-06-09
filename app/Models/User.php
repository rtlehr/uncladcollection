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

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name', 'email', 'password'])]
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
        ];
    }

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

}

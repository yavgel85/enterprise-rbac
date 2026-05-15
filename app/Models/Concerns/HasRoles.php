<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\Role;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasRoles
{
    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class)
            ->withPivot(['assigned_by', 'assigned_at', 'expires_at'])
            ->withTimestamps();
    }

    public function activeRoles(): BelongsToMany
    {
        return $this->roles()->where(function ($query) {
            $query->whereNull('role_user.expires_at')
                ->orWhere('role_user.expires_at', '>', now());
        });
    }

    public function hasRole(string $slug): bool
    {
        return $this->activeRoles()
            ->where('roles.slug', $slug)
            ->exists();
    }

    public function hasAnyRole(array $slugs): bool
    {
        return $this->activeRoles()
            ->whereIn('roles.slug', $slugs)
            ->exists();
    }

    public function maxRoleLevel(): int
    {
        return (int) $this->activeRoles()->max('roles.level');
    }
}

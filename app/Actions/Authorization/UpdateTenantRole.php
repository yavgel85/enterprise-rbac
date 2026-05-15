<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use App\Models\User;
use DomainException;

final readonly class UpdateTenantRole
{
    public function __construct(private ForgetUserPermissionsCache $forget) {}

    /**
     * @param  array{name?: string, slug?: string, description?: string|null, level?: int}  $attributes
     */
    public function handle(User $actor, Role $role, array $attributes): Role
    {
        if ($role->is_system) {
            throw new DomainException('System roles cannot be modified.');
        }

        if (isset($attributes['level']) && ! $actor->is_super_admin) {
            $actorMaxLevel = $actor->maxRoleLevel();
            if ((int) $attributes['level'] >= $actorMaxLevel) {
                throw new DomainException('You cannot set a role level equal to or higher than your own.');
            }
        }

        $role->fill(array_filter(
            $attributes,
            fn ($_, $key) => in_array($key, ['name', 'slug', 'description', 'level'], true),
            ARRAY_FILTER_USE_BOTH
        ))->save();

        $this->forget->forRole($role);

        return $role;
    }
}

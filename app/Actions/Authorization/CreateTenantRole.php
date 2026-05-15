<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Str;

final readonly class CreateTenantRole
{
    /**
     * @param  array{name: string, slug?: string|null, description?: string|null, level?: int|null}  $attributes
     */
    public function handle(User $actor, Tenant $tenant, array $attributes): Role
    {
        $level = (int) ($attributes['level'] ?? 0);

        if (! $actor->is_super_admin) {
            $actorMaxLevel = $actor->maxRoleLevel();
            if ($level >= $actorMaxLevel) {
                throw new DomainException('You cannot create a role with a level equal to or higher than your own.');
            }
        }

        return Role::create([
            'tenant_id' => $tenant->id,
            'name' => $attributes['name'],
            'slug' => $attributes['slug'] ?? Str::slug($attributes['name']),
            'description' => $attributes['description'] ?? null,
            'level' => $level,
            'is_system' => false,
        ]);
    }
}

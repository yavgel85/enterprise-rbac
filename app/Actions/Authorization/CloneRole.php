<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CloneRole
{
    /**
     * Create a new, non-system role inside the tenant that mirrors the
     * permissions (and parent) of the source role.
     *
     * @param  array{name?: string|null, slug?: string|null, level?: int|null}  $overrides
     */
    public function handle(User $actor, Tenant $tenant, Role $source, array $overrides = []): Role
    {
        if ($source->tenant_id !== null && $source->tenant_id !== $tenant->id) {
            throw new DomainException('You can only clone roles from your own tenant.');
        }

        $name = $overrides['name'] ?? $source->name.' (copy)';
        $slug = $overrides['slug'] ?? Str::slug($name);

        // A clone must sit strictly below the source so it never silently
        // becomes a peer of a privileged system role.
        $level = $overrides['level'] ?? max(0, $source->level - 1);

        if (! $actor->is_super_admin && $level >= $actor->maxRoleLevel()) {
            throw new DomainException('You cannot create a role with a level equal to or higher than your own.');
        }

        if (Role::query()->where('tenant_id', $tenant->id)->where('slug', $slug)->exists()) {
            throw new DomainException("A role with slug “{$slug}” already exists.");
        }

        return DB::transaction(function () use ($tenant, $source, $name, $slug, $level) {
            $clone = Role::create([
                'tenant_id' => $tenant->id,
                'parent_id' => $source->parent_id,
                'name' => $name,
                'slug' => $slug,
                'description' => $source->description,
                'level' => $level,
                'is_system' => false,
            ]);

            $clone->permissions()->sync($source->permissions()->pluck('permissions.id')->all());

            return $clone;
        });
    }
}

<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SetRoleParent
{
    public function __construct(private ForgetUserPermissionsCache $forget) {}

    /**
     * Set (or clear, when $parentId is null) the parent of a role, guarding
     * against self-parenting, cross-tenant links and cycles.
     */
    public function handle(User $actor, Role $role, ?int $parentId): void
    {
        if ($role->is_system && ! $actor->is_super_admin) {
            throw new DomainException('Only super-admin can change inheritance on system roles.');
        }

        if ($parentId === null) {
            $this->apply($role, null);

            return;
        }

        if ($parentId === $role->id) {
            throw new DomainException('A role cannot inherit from itself.');
        }

        $parent = Role::query()->withoutGlobalScopes()->find($parentId);

        if ($parent === null) {
            throw new DomainException('Parent role not found.');
        }

        if ($parent->tenant_id !== $role->tenant_id) {
            throw new DomainException('Parent role must belong to the same tenant.');
        }

        if ($this->wouldCreateCycle($role, $parent)) {
            throw new DomainException('This parent would create an inheritance cycle.');
        }

        $this->apply($role, $parent->id);
    }

    private function apply(Role $role, ?int $parentId): void
    {
        DB::transaction(function () use ($role, $parentId) {
            $role->forceFill(['parent_id' => $parentId])->save();

            // The change affects everyone holding this role or any role that
            // (transitively) inherits from it.
            foreach ($this->selfAndDescendants($role) as $affected) {
                $this->forget->forRole($affected);
            }
        });
    }

    /**
     * Walking up from the proposed parent must never reach the role itself.
     */
    private function wouldCreateCycle(Role $role, Role $parent): bool
    {
        return $parent->selfAndAncestors()->contains(fn (Role $r) => $r->id === $role->id);
    }

    /**
     * @return list<Role>
     */
    private function selfAndDescendants(Role $role, int $maxDepth = 20): array
    {
        $all = [$role];
        $frontier = [$role->id];
        $seen = [$role->id => true];
        $depth = 0;

        while ($frontier !== [] && $depth < $maxDepth) {
            $children = Role::query()
                ->withoutGlobalScopes()
                ->whereIn('parent_id', $frontier)
                ->get();

            $next = [];
            foreach ($children as $child) {
                if (isset($seen[$child->id])) {
                    continue;
                }
                $seen[$child->id] = true;
                $all[] = $child;
                $next[] = $child->id;
            }

            $frontier = $next;
            $depth++;
        }

        return $all;
    }
}

<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Enums\Permission;

final readonly class RoleDefinition
{
    /**
     * @param  list<Permission>  $permissions
     */
    public function __construct(
        public string $slug,
        public string $name,
        public int $level,
        public array $permissions,
        public string $description = '',
    ) {}

    public function has(Permission $permission): bool
    {
        return in_array($permission, $this->permissions, true);
    }

    /**
     * @return list<string>
     */
    public function permissionSlugs(): array
    {
        return array_map(fn (Permission $p) => $p->value, $this->permissions);
    }
}

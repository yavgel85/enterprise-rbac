<?php

declare(strict_types=1);

namespace App\Authorization\Constraints;

use DomainException;

final class RoleAssignmentConstraint
{
    /**
     * @return list<array{0: string, 1: string}>
     */
    public function forbiddenPairs(): array
    {
        return config('rbac.forbidden_role_pairs', []);
    }

    /**
     * @param  list<string>  $roleSlugs
     */
    public function assertValid(array $roleSlugs): void
    {
        foreach ($this->forbiddenPairs() as [$first, $second]) {
            if (in_array($first, $roleSlugs, true) && in_array($second, $roleSlugs, true)) {
                throw new DomainException(
                    "Roles [{$first}] and [{$second}] cannot be combined for the same user."
                );
            }
        }
    }
}

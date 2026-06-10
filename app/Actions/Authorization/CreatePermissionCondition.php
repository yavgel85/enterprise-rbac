<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Authorization\AbacGate;
use App\Enums\AuditAction;
use App\Models\PermissionCondition;
use App\Models\Tenant;
use Illuminate\Validation\ValidationException;

final readonly class CreatePermissionCondition
{
    public function __construct(private LogAuditEvent $audit) {}

    /**
     * @param  array<string, mixed>  $conditions
     */
    public function handle(
        Tenant $tenant,
        int $permissionId,
        ?int $roleId,
        array $conditions,
        ?string $description = null,
    ): PermissionCondition {
        $this->assertValidStructure($conditions);

        $condition = PermissionCondition::create([
            'tenant_id' => $tenant->id,
            'permission_id' => $permissionId,
            'role_id' => $roleId,
            'conditions' => $conditions,
            'description' => $description,
        ]);

        AbacGate::flushCache();

        $this->audit->handle(AuditAction::PermissionConditionCreated, $condition, [
            'permission_id' => $permissionId,
            'role_id' => $roleId,
        ]);

        return $condition;
    }

    /**
     * Light structural validation of the condition DSL so the UI cannot persist
     * conditions that can never evaluate.
     *
     * @param  array<string, mixed>  $node
     */
    private function assertValidStructure(array $node): void
    {
        if ($node === []) {
            throw ValidationException::withMessages([
                'conditions' => 'Conditions cannot be empty.',
            ]);
        }

        $this->walk($node);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private function walk(array $node): void
    {
        foreach (['all', 'any'] as $group) {
            if (array_key_exists($group, $node)) {
                if (! is_array($node[$group])) {
                    $this->fail("\"{$group}\" must be a list of conditions.");
                }

                foreach ($node[$group] as $child) {
                    $this->walk((array) $child);
                }

                return;
            }
        }

        if (array_key_exists('not', $node)) {
            $this->walk((array) $node['not']);

            return;
        }

        if (! isset($node['attr']) || ! is_string($node['attr'])) {
            $this->fail('Each leaf condition needs a string "attr".');
        }

        $allowedOps = ['=', '==', '!=', '<>', '>', '<', '>=', '<=', 'in', 'not_in', 'contains'];

        if (isset($node['op']) && ! in_array($node['op'], $allowedOps, true)) {
            $this->fail('Unsupported operator: '.$node['op']);
        }
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['conditions' => $message]);
    }
}

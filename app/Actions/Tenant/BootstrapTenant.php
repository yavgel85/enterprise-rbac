<?php

declare(strict_types=1);

namespace App\Actions\Tenant;

use App\Actions\Audit\LogAuditEvent;
use App\Authorization\RoleRegistry;
use App\Enums\AuditAction;
use App\Models\Department;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class BootstrapTenant
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(Tenant $tenant, ?User $firstAdmin = null): void
    {
        DB::transaction(function () use ($tenant, $firstAdmin) {
            $permissions = Permission::query()->pluck('id', 'slug');

            foreach (RoleRegistry::all() as $definition) {
                $role = Role::create([
                    'tenant_id' => $tenant->id,
                    'name' => $definition->name,
                    'slug' => $definition->slug,
                    'description' => $definition->description,
                    'level' => $definition->level,
                    'is_system' => false,
                ]);

                $ids = $permissions
                    ->only($definition->permissionSlugs())
                    ->values()
                    ->all();

                if ($ids !== []) {
                    $role->permissions()->sync($ids);
                }
            }

            Department::firstOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'general'],
                ['name' => 'General']
            );

            if ($firstAdmin) {
                $firstAdmin->update(['tenant_id' => $tenant->id]);

                $adminRole = Role::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('slug', 'tenant-admin')
                    ->firstOrFail();

                $firstAdmin->roles()->syncWithoutDetaching([
                    $adminRole->id => [
                        'assigned_at' => now(),
                    ],
                ]);
            }

            $this->audit->handle(AuditAction::TenantBootstrapped, $tenant);
        });
    }
}

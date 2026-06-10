<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Actions\Tenant\BootstrapTenant;
use App\Authorization\AbacGate;
use App\Enums\CompanyStatus;
use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Enums\DirectPermissionType;
use App\Enums\TaskStatus;
use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Feature;
use App\Models\Permission;
use App\Models\PermissionCondition;
use App\Models\ResourcePermission;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoTenantSeeder extends Seeder
{
    public function __construct(private readonly BootstrapTenant $bootstrap) {}

    public function run(): void
    {
        $this->seedAcme();
        $this->seedGlobex();

        AbacGate::flushCache();
    }

    private function seedAcme(): void
    {
        $tenant = Tenant::create([
            'name' => 'Acme Corp',
            'slug' => 'acme',
            'is_active' => true,
        ]);

        $this->bootstrap->handle($tenant);

        $sales = Department::create(['tenant_id' => $tenant->id, 'name' => 'Sales', 'slug' => 'sales']);
        Department::create(['tenant_id' => $tenant->id, 'name' => 'Support', 'slug' => 'support']);
        Department::create(['tenant_id' => $tenant->id, 'name' => 'Finance', 'slug' => 'finance']);

        $this->enableFeature($tenant, 'advanced_analytics');
        $this->enableFeature($tenant, 'audit_export');

        $admin = $this->createUser($tenant, $sales, 'Acme Admin', 'admin@acme.test');
        $manager = $this->createUser($tenant, $sales, 'Mary Manager', 'manager@acme.test');
        $sales1 = $this->createUser($tenant, $sales, 'Sam Sales', 'sales@acme.test');
        $auditor = $this->createUser($tenant, null, 'Alice Auditor', 'auditor@acme.test');
        $viewer = $this->createUser($tenant, null, 'Vince Viewer', 'viewer@acme.test');
        $multi = $this->createUser($tenant, $sales, 'Mia Multi-Role', 'multi@acme.test');
        $temp = $this->createUser($tenant, $sales, 'Tom Temporary', 'temp@acme.test');

        $this->attachRole($admin, $tenant, 'tenant-admin');
        $this->attachRole($manager, $tenant, 'manager');
        $this->attachRole($sales1, $tenant, 'sales');
        $this->attachRole($auditor, $tenant, 'auditor');
        $this->attachRole($viewer, $tenant, 'viewer');

        $this->attachRole($multi, $tenant, 'manager');
        $this->attachRole($multi, $tenant, 'sales');

        $this->attachRole($temp, $tenant, 'sales', expiresAt: now()->addDays(7));

        $this->seedCrmData($tenant, $sales, $sales1, $manager);

        // 2.4 ABAC: closed deals can never be deleted, regardless of role.
        $this->attachPermissionCondition(
            $tenant,
            'deals.delete',
            null,
            ['attr' => 'deal.status', 'op' => '!=', 'value' => 'closed'],
            'Closed deals cannot be deleted',
        );

        // 2.8 ReBAC: the viewer gets edit access to a single deal instance.
        $oneDeal = Deal::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->attachResourcePermission($tenant, $viewer, 'deals.update', $oneDeal, $admin);
    }

    private function seedGlobex(): void
    {
        $tenant = Tenant::create([
            'name' => 'Globex Inc',
            'slug' => 'globex',
            'is_active' => true,
        ]);

        $this->bootstrap->handle($tenant);

        $sales = Department::create(['tenant_id' => $tenant->id, 'name' => 'Sales', 'slug' => 'sales']);
        $support = Department::create(['tenant_id' => $tenant->id, 'name' => 'Support', 'slug' => 'support']);
        Department::create(['tenant_id' => $tenant->id, 'name' => 'Finance', 'slug' => 'finance']);

        $this->enableFeature($tenant, 'audit_export');

        $admin = $this->createUser($tenant, null, 'Globex Admin', 'admin@globex.test');
        $manager = $this->createUser($tenant, $support, 'Greg Manager', 'manager@globex.test');
        $sales1 = $this->createUser($tenant, $sales, 'Sara Sales', 'sales@globex.test');
        $auditor = $this->createUser($tenant, null, 'Anna Auditor', 'auditor@globex.test');
        $viewer = $this->createUser($tenant, null, 'Vera Viewer', 'viewer@globex.test');
        $granted = $this->createUser($tenant, $sales, 'Grace Granted', 'granted@globex.test');
        $denied = $this->createUser($tenant, $sales, 'Dan Denied', 'denied@globex.test');

        $this->attachRole($admin, $tenant, 'tenant-admin');
        $this->attachRole($manager, $tenant, 'manager');
        $this->attachRole($sales1, $tenant, 'sales');
        $this->attachRole($auditor, $tenant, 'auditor');
        $this->attachRole($viewer, $tenant, 'viewer');

        $this->attachRole($granted, $tenant, 'viewer');
        $this->attachDirectPermission($granted, 'deals.update', DirectPermissionType::Grant, 'Special access for senior viewer');

        $this->attachRole($denied, $tenant, 'tenant-admin');
        $this->attachDirectPermission($denied, 'deals.delete', DirectPermissionType::Deny, 'Demo: deny override on admin role');

        $this->seedCrmData($tenant, $sales, $sales1, $manager);
    }

    private function createUser(Tenant $tenant, ?Department $department, string $name, string $email): User
    {
        return User::create([
            'tenant_id' => $tenant->id,
            'department_id' => $department?->id,
            'name' => $name,
            'email' => $email,
            'password' => Hash::make('password'),
            'is_active' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function attachRole(User $user, Tenant $tenant, string $slug, ?\DateTimeInterface $expiresAt = null): void
    {
        $role = Role::query()
            ->where('tenant_id', $tenant->id)
            ->where('slug', $slug)
            ->firstOrFail();

        $user->roles()->syncWithoutDetaching([
            $role->id => [
                'assigned_at' => now(),
                'expires_at' => $expiresAt,
            ],
        ]);
    }

    private function attachDirectPermission(User $user, string $slug, DirectPermissionType $type, ?string $reason = null): void
    {
        $permission = Permission::query()->where('slug', $slug)->firstOrFail();

        $user->directPermissions()->syncWithoutDetaching([
            $permission->id => [
                'type' => $type->value,
                'reason' => $reason,
            ],
        ]);
    }

    /**
     * @param  array<string, mixed>  $conditions
     */
    private function attachPermissionCondition(Tenant $tenant, string $slug, ?int $roleId, array $conditions, ?string $description = null): void
    {
        $permission = Permission::query()->where('slug', $slug)->firstOrFail();

        PermissionCondition::create([
            'tenant_id' => $tenant->id,
            'permission_id' => $permission->id,
            'role_id' => $roleId,
            'conditions' => $conditions,
            'description' => $description,
        ]);
    }

    private function attachResourcePermission(Tenant $tenant, User $user, string $slug, Deal $resource, User $assignedBy): void
    {
        $permission = Permission::query()->where('slug', $slug)->firstOrFail();

        ResourcePermission::create([
            'tenant_id' => $tenant->id,
            'user_id' => $user->id,
            'permission_id' => $permission->id,
            'resource_type' => $resource->getMorphClass(),
            'resource_id' => $resource->getKey(),
            'assigned_by' => $assignedBy->id,
        ]);
    }

    private function enableFeature(Tenant $tenant, string $slug): void
    {
        $feature = Feature::query()->where('slug', $slug)->firstOrFail();

        $tenant->features()->syncWithoutDetaching([
            $feature->id => ['is_enabled' => true],
        ]);
    }

    private function seedCrmData(Tenant $tenant, Department $department, User $owner, User $manager): void
    {
        $companies = Company::factory()
            ->count(5)
            ->state(fn () => [
                'tenant_id' => $tenant->id,
                'owner_id' => $owner->id,
                'created_by' => $owner->id,
                'status' => CompanyStatus::Active->value,
            ])
            ->create();

        foreach ($companies as $company) {
            $contacts = Contact::factory()
                ->count(2)
                ->state(fn () => [
                    'tenant_id' => $tenant->id,
                    'company_id' => $company->id,
                    'owner_id' => $owner->id,
                    'created_by' => $owner->id,
                ])
                ->create();

            $deal = Deal::factory()
                ->state(fn () => [
                    'tenant_id' => $tenant->id,
                    'company_id' => $company->id,
                    'contact_id' => $contacts->first()->id,
                    'department_id' => $department->id,
                    'owner_id' => $owner->id,
                    'created_by' => $owner->id,
                    'stage' => DealStage::Proposal->value,
                    'status' => DealStatus::Draft->value,
                ])
                ->create();

            Task::factory()
                ->count(2)
                ->state(fn () => [
                    'tenant_id' => $tenant->id,
                    'taskable_type' => Deal::class,
                    'taskable_id' => $deal->id,
                    'assignee_id' => $owner->id,
                    'created_by' => $manager->id,
                    'status' => TaskStatus::Open->value,
                ])
                ->create();

            Activity::factory()
                ->count(3)
                ->state(fn () => [
                    'tenant_id' => $tenant->id,
                    'subjectable_type' => Deal::class,
                    'subjectable_id' => $deal->id,
                    'user_id' => $owner->id,
                ])
                ->create();
        }
    }
}

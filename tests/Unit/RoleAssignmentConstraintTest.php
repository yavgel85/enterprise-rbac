<?php

declare(strict_types=1);

use App\Actions\Authorization\AssignRolesToUser;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
    $this->action = app(AssignRolesToUser::class);
});

it('rejects forbidden role combinations', function () {
    $actor = User::factory()->superAdmin()->create();
    $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $auditor = tenantRole($this->tenant, 'auditor');
    $manager = tenantRole($this->tenant, 'manager');

    $this->action->handle($actor, $member, [$auditor->id, $manager->id]);
})->throws(DomainException::class, 'auditor');

it('prevents privilege escalation: cannot assign higher or equal level role', function () {
    $actor = makeUserWithRole($this->tenant, 'manager');
    $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $admin = tenantRole($this->tenant, 'tenant-admin');

    $this->action->handle($actor, $member, [$admin->id]);
})->throws(DomainException::class, 'higher than your own');

it('allows tenant admin to assign lower roles', function () {
    $actor = makeUserWithRole($this->tenant, 'tenant-admin');
    $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $sales = tenantRole($this->tenant, 'sales');

    $this->action->handle($actor, $member, [$sales->id]);

    expect($member->fresh()->roles->pluck('slug')->all())->toContain('sales');
});

it('prevents assigning a role from another tenant', function () {
    $other = makeTenant('globex', 'Globex');
    $actor = User::factory()->superAdmin()->create();
    $member = User::factory()->create(['tenant_id' => $this->tenant->id]);

    $foreignRole = tenantRole($other, 'sales');

    $this->action->handle($actor, $member, [$foreignRole->id]);
})->throws(DomainException::class, 'not available for this tenant');

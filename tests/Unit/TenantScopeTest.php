<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Context;

beforeEach(function () {
    $this->tenant = Tenant::factory()->create();
    $this->other = Tenant::factory()->create();

    Company::factory()->count(3)->create(['tenant_id' => $this->tenant->id]);
    Company::factory()->count(2)->create(['tenant_id' => $this->other->id]);
});

it('scopes queries to the current tenant id from the context', function () {
    Context::add('tenant_id', $this->tenant->id);

    expect(Company::count())->toBe(3);
});

it('bypasses scope for super admins', function () {
    $super = User::factory()->superAdmin()->create();
    $this->actingAs($super);

    expect(Company::count())->toBe(5);
});

it('falls back to the user tenant if no context is set', function () {
    $member = User::factory()->create(['tenant_id' => $this->other->id]);
    $this->actingAs($member);

    expect(Company::count())->toBe(2);
});

it('can be bypassed with withoutGlobalScopes()', function () {
    expect(Company::query()->withoutGlobalScopes()->count())->toBe(5);
});

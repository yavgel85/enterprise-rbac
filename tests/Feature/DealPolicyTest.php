<?php

declare(strict_types=1);

use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Department;
use Carbon\Carbon;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('blocks updates to deals that are not in draft status', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'owner_id' => $sales->id,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($sales)
        ->put(route('crm.deals.update', [$this->tenant, $deal]), [
            'title' => 'Try update',
            'amount' => 1,
            'currency' => 'USD',
            'stage' => 'lead',
            'status' => DealStatus::Draft->value,
            'probability' => 10,
        ])
        ->assertForbidden();
});

it('allows owner to update a draft deal', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'owner_id' => $sales->id,
        'status' => DealStatus::Draft->value,
    ]);

    $this->actingAs($sales)
        ->put(route('crm.deals.update', [$this->tenant, $deal]), [
            'title' => 'Owner update',
            'amount' => 100,
            'currency' => 'USD',
            'stage' => 'lead',
            'status' => DealStatus::Draft->value,
            'probability' => 10,
        ])
        ->assertRedirect();

    expect($deal->fresh()->title)->toBe('Owner update');
});

it('blocks a non-owner from updating a draft deal in a different department', function () {
    $owner = makeUserWithRole($this->tenant, 'sales');
    $intruder = makeUserWithRole($this->tenant, 'sales', [
        'department_id' => Department::factory()->create(['tenant_id' => $this->tenant->id])->id,
    ]);

    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'owner_id' => $owner->id,
        'department_id' => Department::factory()->create(['tenant_id' => $this->tenant->id])->id,
        'status' => DealStatus::Draft->value,
    ]);

    $this->actingAs($intruder)
        ->put(route('crm.deals.update', [$this->tenant, $deal]), [
            'title' => 'Hack',
            'amount' => 1,
            'currency' => 'USD',
            'stage' => 'lead',
            'status' => DealStatus::Draft->value,
            'probability' => 10,
        ])
        ->assertForbidden();
});

it('allows manager to approve a deal during business hours', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 12, 11, 0, 0));

    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'status' => DealStatus::Draft->value]);

    $this->actingAs($manager)
        ->post(route('crm.deals.approve', [$this->tenant, $deal]))
        ->assertRedirect();

    expect($deal->fresh()->stage)->toBe(DealStage::Won);
});

it('forbids approving deals outside business hours', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 12, 22, 0, 0));

    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'status' => DealStatus::Draft->value]);

    $this->actingAs($manager)
        ->post(route('crm.deals.approve', [$this->tenant, $deal]))
        ->assertForbidden();
});

it('forbids approving deals on weekends', function () {
    Carbon::setTestNow(Carbon::create(2026, 5, 16, 11, 0, 0));

    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'status' => DealStatus::Draft->value]);

    $this->actingAs($manager)
        ->post(route('crm.deals.approve', [$this->tenant, $deal]))
        ->assertForbidden();
});

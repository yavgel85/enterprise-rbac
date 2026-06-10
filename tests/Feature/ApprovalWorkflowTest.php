<?php

declare(strict_types=1);

use App\Enums\ApprovalStatus;
use App\Enums\DealStatus;
use App\Models\ApprovalRequest;
use App\Models\Deal;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->tenant = makeTenant();
    // Pin "now" to a weekday business hour so DealPolicy::approve passes.
    $this->travelTo(Carbon::parse('2026-06-08 10:00:00'));
});

it('closes small deals immediately without an approval request', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 5000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($manager)
        ->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve")
        ->assertRedirect();

    expect($deal->fresh()->status)->toBe(DealStatus::Closed);
    expect(ApprovalRequest::count())->toBe(0);
});

it('creates a pending multi-step request for high-value deals', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 250000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($manager)
        ->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve")
        ->assertRedirect();

    expect($deal->fresh()->status)->toBe(DealStatus::PendingApproval);

    $request = ApprovalRequest::firstOrFail();
    expect($request->status)->toBe(ApprovalStatus::Pending);
    expect($request->steps()->count())->toBe(2);
    expect($request->current_step)->toBe(1);
});

it('prevents the requester from deciding their own request', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 250000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($manager)->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve");
    $request = ApprovalRequest::firstOrFail();

    $this->actingAs($manager)
        ->post("/t/{$this->tenant->slug}/crm/approvals/{$request->id}/decide", ['decision' => 'approve'])
        ->assertSessionHas('error');

    expect($request->fresh()->current_step)->toBe(1);
});

it('completes the flow when two distinct approvers approve each step', function () {
    $managerA = makeUserWithRole($this->tenant, 'manager');
    $managerB = makeUserWithRole($this->tenant, 'manager');
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 250000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($managerA)->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve");
    $request = ApprovalRequest::firstOrFail();

    // Step 1: a different manager.
    $this->actingAs($managerB)
        ->post("/t/{$this->tenant->slug}/crm/approvals/{$request->id}/decide", ['decision' => 'approve'])
        ->assertRedirect();
    expect($request->fresh()->current_step)->toBe(2);

    // Step 2: tenant-admin.
    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/crm/approvals/{$request->id}/decide", ['decision' => 'approve'])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(ApprovalStatus::Approved);
    expect($deal->fresh()->status)->toBe(DealStatus::Closed);
});

it('reverts the deal to active when a step is rejected', function () {
    $managerA = makeUserWithRole($this->tenant, 'manager');
    $managerB = makeUserWithRole($this->tenant, 'manager');

    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 250000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($managerA)->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve");
    $request = ApprovalRequest::firstOrFail();

    $this->actingAs($managerB)
        ->post("/t/{$this->tenant->slug}/crm/approvals/{$request->id}/decide", [
            'decision' => 'reject',
            'note' => 'Margins too thin',
        ])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(ApprovalStatus::Rejected);
    expect($deal->fresh()->status)->toBe(DealStatus::Active);
});

it('only lists decidable pending requests for the current approver', function () {
    $managerA = makeUserWithRole($this->tenant, 'manager');
    $managerB = makeUserWithRole($this->tenant, 'manager');

    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'amount' => 250000,
        'status' => DealStatus::Active->value,
    ]);

    $this->actingAs($managerA)->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/approve");

    expect(ApprovalRequest::pendingForUser($managerB)->count())->toBe(1);
    expect(ApprovalRequest::pendingForUser($managerA)->count())->toBe(0); // requester excluded
});

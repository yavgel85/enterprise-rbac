<?php

declare(strict_types=1);

use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Feature;

beforeEach(function () {
    $this->tenant = makeTenant();
});

function enableAnalytics($tenant): void
{
    $feature = Feature::query()->where('slug', 'advanced_analytics')->firstOrFail();
    $tenant->features()->attach($feature->id, ['is_enabled' => true]);
}

it('blocks the analytics page when the feature flag is disabled', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');

    $this->actingAs($manager)
        ->get(route('crm.reports.analytics', $this->tenant))
        ->assertForbidden();
});

it('blocks the analytics page without the reports.view permission', function () {
    enableAnalytics($this->tenant);
    $sales = makeUserWithRole($this->tenant, 'sales');

    $this->actingAs($sales)
        ->get(route('crm.reports.analytics', $this->tenant))
        ->assertForbidden();
});

it('shows pipeline analytics to a manager when enabled', function () {
    enableAnalytics($this->tenant);
    $manager = makeUserWithRole($this->tenant, 'manager');

    Deal::factory()->won()->count(2)->create(['tenant_id' => $this->tenant->id, 'owner_id' => $manager->id, 'amount' => 1000]);
    Deal::factory()->count(3)->create([
        'tenant_id' => $this->tenant->id,
        'stage' => DealStage::Lost->value,
        'lost_reason' => 'Price',
    ]);

    $this->actingAs($manager)
        ->get(route('crm.reports.analytics', $this->tenant))
        ->assertOk()
        ->assertSee('Pipeline analytics')
        ->assertSee('Price');
});

it('streams a PDF report', function () {
    enableAnalytics($this->tenant);
    $manager = makeUserWithRole($this->tenant, 'manager');

    Deal::factory()->won()->create(['tenant_id' => $this->tenant->id, 'owner_id' => $manager->id]);

    $response = $this->actingAs($manager)->get(route('crm.reports.analytics.pdf', $this->tenant));

    $response->assertOk();
    expect($response->headers->get('content-type'))->toContain('application/pdf');
});

it('requires a lost reason when a deal is marked lost', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');

    $this->actingAs($sales)
        ->post(route('crm.deals.store', $this->tenant), [
            'title' => 'Big deal',
            'amount' => 5000,
            'currency' => 'USD',
            'stage' => DealStage::Lost->value,
            'status' => DealStatus::Draft->value,
            'probability' => 0,
        ])
        ->assertSessionHasErrors('lost_reason');
});

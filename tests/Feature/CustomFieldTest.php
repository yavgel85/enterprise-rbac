<?php

declare(strict_types=1);

use App\Enums\CompanyStatus;
use App\Enums\CustomFieldType;
use App\Models\Company;
use App\Models\CustomFieldDefinition;

beforeEach(function () {
    $this->tenant = makeTenant();
});

function defineField(int $tenantId, array $overrides = []): CustomFieldDefinition
{
    return CustomFieldDefinition::create(array_merge([
        'tenant_id' => $tenantId,
        'model_type' => Company::class,
        'key' => 'industry_code',
        'label' => 'Industry code',
        'type' => CustomFieldType::Text,
        'options' => null,
        'required' => false,
        'position' => 0,
    ], $overrides));
}

it('lets an admin create a custom field definition', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('admin.custom-fields.store', $this->tenant), [
            'model' => 'company',
            'label' => 'Account tier',
            'key' => 'account_tier',
            'type' => 'select',
            'options' => "gold\nsilver\nbronze",
            'required' => '1',
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $definition = CustomFieldDefinition::query()->firstOrFail();
    expect($definition->model_type)->toBe(Company::class)
        ->and($definition->options)->toBe(['gold', 'silver', 'bronze'])
        ->and($definition->required)->toBeTrue();
});

it('blocks users without custom-fields.manage', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');

    $this->actingAs($manager)
        ->get(route('admin.custom-fields.index', $this->tenant))
        ->assertForbidden();
});

it('persists custom field values when creating a company', function () {
    defineField($this->tenant->id);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'Acme',
            'status' => CompanyStatus::cases()[0]->value,
            'custom_fields' => ['industry_code' => 'NAICS-541'],
        ])
        ->assertRedirect();

    $company = Company::query()->firstOrFail();
    expect($company->cf('industry_code'))->toBe('NAICS-541');
});

it('enforces required custom fields', function () {
    defineField($this->tenant->id, ['key' => 'mandatory', 'required' => true]);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'Acme',
            'status' => CompanyStatus::cases()[0]->value,
            'custom_fields' => ['mandatory' => ''],
        ])
        ->assertSessionHasErrors('custom_fields.mandatory');

    expect(Company::query()->count())->toBe(0);
});

it('rejects values outside a select definition options', function () {
    defineField($this->tenant->id, [
        'key' => 'tier',
        'type' => CustomFieldType::Select,
        'options' => ['gold', 'silver'],
    ]);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'Acme',
            'status' => CompanyStatus::cases()[0]->value,
            'custom_fields' => ['tier' => 'platinum'],
        ])
        ->assertSessionHasErrors('custom_fields.tier');
});

it('casts number and boolean custom fields to typed values', function () {
    defineField($this->tenant->id, ['key' => 'score', 'type' => CustomFieldType::Number]);
    defineField($this->tenant->id, ['key' => 'vip', 'type' => CustomFieldType::Boolean]);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)->post(route('crm.companies.store', $this->tenant), [
        'name' => 'Acme',
        'status' => CompanyStatus::cases()[0]->value,
        'custom_fields' => ['score' => '42', 'vip' => '1'],
    ])->assertRedirect();

    $company = Company::query()->firstOrFail();
    expect($company->cf('score'))->toEqual(42)
        ->and($company->cf('vip'))->toBeTrue();
});

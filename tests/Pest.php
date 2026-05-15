<?php

declare(strict_types=1);

use App\Actions\Tenant\BootstrapTenant;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

pest()
    ->extend(TestCase::class)
    ->use(LazilyRefreshDatabase::class)
    ->in('Feature', 'Unit');

expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Test helpers
|--------------------------------------------------------------------------
*/

function seedRbacCatalog(): void
{
    (new ModuleSeeder)->run();
    (new PermissionSeeder)->run();
    (new FeatureSeeder)->run();
}

/**
 * Create a tenant with bootstrap (all system roles, default department).
 */
function makeTenant(string $slug = 'acme', string $name = 'Acme'): Tenant
{
    seedRbacCatalog();

    $tenant = Tenant::factory()->create([
        'slug' => $slug,
        'name' => $name,
    ]);

    app(BootstrapTenant::class)->handle($tenant);

    return $tenant;
}

function tenantRole(Tenant $tenant, string $slug): Role
{
    return Role::query()
        ->where('tenant_id', $tenant->id)
        ->where('slug', $slug)
        ->firstOrFail();
}

function makeUserWithRole(Tenant $tenant, string $roleSlug, array $overrides = []): User
{
    $user = User::factory()->create(array_merge(['tenant_id' => $tenant->id], $overrides));
    $user->roles()->attach(tenantRole($tenant, $roleSlug)->id, ['assigned_at' => now()]);

    return $user;
}

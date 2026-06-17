<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\Deal;
use Illuminate\Support\Facades\DB;

/**
 * N+1 regression guards (Improvement 4.3). Each page is hit with a handful of
 * related rows; the assertion fails loudly if eager-loading regresses and the
 * query count starts scaling with the number of rows.
 */
beforeEach(function () {
    $this->tenant = makeTenant();
});

function countQueries(Closure $callback): int
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('does not N+1 on the admin users index', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    foreach (range(1, 5) as $i) {
        makeUserWithRole($this->tenant, 'sales', ['email' => "member{$i}@acme.test"]);
    }

    $queries = countQueries(function () use ($admin) {
        $this->actingAs($admin->fresh())
            ->get(route('admin.users.index', $this->tenant))
            ->assertOk();
    });

    expect($queries)->toBeLessThan(30);
});

it('does not N+1 on the dashboard', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    Deal::factory()->count(8)->create([
        'tenant_id' => $this->tenant->id,
        'owner_id' => $admin->id,
    ]);

    $queries = countQueries(function () use ($admin) {
        $this->actingAs($admin->fresh())
            ->get(route('tenant.dashboard', $this->tenant))
            ->assertOk();
    });

    expect($queries)->toBeLessThan(40);
});

it('does not N+1 on the admin audit index', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    foreach (range(1, 8) as $i) {
        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $admin->id,
            'action' => 'created',
            'created_at' => now(),
        ]);
    }

    $queries = countQueries(function () use ($admin) {
        $this->actingAs($admin->fresh())
            ->get(route('admin.audit.index', $this->tenant))
            ->assertOk();
    });

    expect($queries)->toBeLessThan(30);
});

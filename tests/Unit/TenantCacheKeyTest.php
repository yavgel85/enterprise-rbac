<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;

it('prefixes a key with the tenant id from context', function () {
    Context::add('tenant_id', 42);

    expect(tenant_cache_key('companies.list'))->toBe('tenant:42:companies.list');
});

it('accepts an explicit tenant id for console contexts', function () {
    expect(tenant_cache_key('companies.list', 7))->toBe('tenant:7:companies.list');
});

it('falls back to a none marker when no tenant is resolvable', function () {
    expect(tenant_cache_key('companies.list'))->toBe('tenant:none:companies.list');
});

it('exposes a camelCase alias', function () {
    expect(tenantCacheKey('x', 5))->toBe(tenant_cache_key('x', 5));
});

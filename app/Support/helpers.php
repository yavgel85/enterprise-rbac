<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Context;

if (! function_exists('tenant_cache_key')) {
    /**
     * Build a cache key namespaced to the current tenant (Improvement 4.7).
     *
     * Every tenant-scoped cache entry MUST be prefixed with the tenant id so two
     * tenants can never read each other's cached data. The tenant is resolved
     * from the request Context (set by ResolveTenant middleware) and falls back
     * to the authenticated user's tenant. Pass an explicit id for console/queue
     * contexts where no request scope exists.
     */
    function tenant_cache_key(string $suffix, ?int $tenantId = null): string
    {
        $tenantId ??= Context::get('tenant_id') ?? auth()->user()?->tenant_id;

        return 'tenant:'.($tenantId ?? 'none').':'.$suffix;
    }
}

if (! function_exists('tenantCacheKey')) {
    /**
     * camelCase alias for {@see tenant_cache_key()}.
     */
    function tenantCacheKey(string $suffix, ?int $tenantId = null): string
    {
        return tenant_cache_key($suffix, $tenantId);
    }
}

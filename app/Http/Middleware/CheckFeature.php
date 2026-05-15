<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckFeature
{
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $tenant = app('current_tenant');

        if (! $tenant instanceof Tenant) {
            abort(403, 'No tenant context for feature check.');
        }

        if (! $tenant->hasFeature($feature)) {
            abort(403, "Feature [{$feature}] is not enabled for this tenant.");
        }

        return $next($request);
    }
}

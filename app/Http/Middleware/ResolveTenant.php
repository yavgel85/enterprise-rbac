<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenant = $request->route('tenant');

        if (! $tenant instanceof Tenant) {
            return $next($request);
        }

        if (! $tenant->is_active) {
            abort(403, 'This tenant is currently inactive.');
        }

        $user = $request->user();

        if ($user && ! $user->is_super_admin && $user->tenant_id !== $tenant->id) {
            abort(403, 'You do not have access to this tenant.');
        }

        Context::add('tenant_id', $tenant->id);
        app()->instance('current_tenant', $tenant);

        return $next($request);
    }
}

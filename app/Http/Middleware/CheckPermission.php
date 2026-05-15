<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\Audit\LogAuditEvent;
use App\Actions\Authorization\ResolveUserPermissions;
use App\Enums\AuditAction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    public function __construct(
        private readonly ResolveUserPermissions $resolve,
        private readonly LogAuditEvent $audit,
    ) {}

    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($user->is_super_admin) {
            return $next($request);
        }

        if (! isset($this->resolve->handle($user)[$permission])) {
            $this->audit->handle(AuditAction::PermissionDenied, metadata: [
                'permission' => $permission,
                'route' => $request->path(),
            ]);

            abort(403, "Missing permission: {$permission}");
        }

        return $next($request);
    }
}

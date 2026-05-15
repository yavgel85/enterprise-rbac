<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Actions\Authorization\ResolveUserPermissions;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasPermissions
{
    public function directPermissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user')
            ->withPivot(['type', 'expires_at', 'assigned_by', 'reason'])
            ->withTimestamps();
    }

    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if ($this->is_super_admin) {
            return true;
        }

        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return isset(app(ResolveUserPermissions::class)->handle($this)[$slug]);
    }

    /**
     * @return array<string, true>
     */
    public function allPermissions(): array
    {
        if ($this->is_super_admin) {
            return collect(PermissionEnum::cases())
                ->mapWithKeys(fn (PermissionEnum $p) => [$p->value => true])
                ->all();
        }

        return app(ResolveUserPermissions::class)->handle($this);
    }
}

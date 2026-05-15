<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            app(LogAuditEvent::class)->forModel(AuditAction::Created, $model);
        });

        static::updated(function ($model) {
            app(LogAuditEvent::class)->forModel(AuditAction::Updated, $model);
        });

        static::deleted(function ($model) {
            app(LogAuditEvent::class)->forModel(AuditAction::Deleted, $model);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                app(LogAuditEvent::class)->forModel(AuditAction::Restored, $model);
            });
        }
    }
}

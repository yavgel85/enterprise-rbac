<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class AuditSink extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'type',
        'endpoint',
        'secret',
        'events',
        'is_active',
        'last_delivered_at',
        'last_failed_at',
        'last_error',
    ];

    protected $hidden = [
        'secret',
    ];

    protected function casts(): array
    {
        return [
            'events' => 'array',
            'is_active' => 'boolean',
            'last_delivered_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }

    /**
     * Whether this sink is interested in the given audit action slug.
     */
    public function listensTo(string $action): bool
    {
        $events = $this->events;

        return empty($events) || in_array($action, $events, true);
    }
}

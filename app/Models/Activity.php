<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ActivityType;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ActivityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    /** @use HasFactory<ActivityFactory> */
    use Auditable, BelongsToTenant, HasFactory;

    protected $fillable = [
        'tenant_id',
        'type',
        'subject',
        'body',
        'subjectable_type',
        'subjectable_id',
        'happened_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'type' => ActivityType::class,
            'happened_at' => 'datetime',
        ];
    }

    public function subjectable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use App\Models\Concerns\HasAttachments;
use App\Models\Concerns\HasCustomFields;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use Auditable, BelongsToTenant, HasAttachments, HasCustomFields, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'taskable_type',
        'taskable_id',
        'title',
        'description',
        'due_date',
        'priority',
        'status',
        'assignee_id',
        'created_by',
        'completed_at',
    ];

    protected $attributes = [
        'priority' => 'normal',
        'status' => 'open',
    ];

    protected function casts(): array
    {
        return [
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'due_date' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function taskable(): MorphTo
    {
        return $this->morphTo();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

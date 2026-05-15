<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\BelongsToTenant;
use Database\Factories\DealFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Deal extends Model
{
    /** @use HasFactory<DealFactory> */
    use Auditable, BelongsToTenant, HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'company_id',
        'contact_id',
        'department_id',
        'title',
        'amount',
        'currency',
        'stage',
        'probability',
        'expected_close_date',
        'closed_at',
        'owner_id',
        'created_by',
        'status',
    ];

    protected $attributes = [
        'amount' => 0,
        'currency' => 'USD',
        'stage' => 'lead',
        'probability' => 0,
        'status' => 'draft',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'probability' => 'integer',
            'stage' => DealStage::class,
            'status' => DealStatus::class,
            'expected_close_date' => 'date',
            'closed_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks(): MorphMany
    {
        return $this->morphMany(Task::class, 'taskable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subjectable');
    }
}

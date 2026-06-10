<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_request_id',
        'step',
        'approver_role_id',
        'decided_by',
        'decided_at',
        'decision',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    public function request(): BelongsTo
    {
        return $this->belongsTo(ApprovalRequest::class, 'approval_request_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class, 'approver_role_id');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}

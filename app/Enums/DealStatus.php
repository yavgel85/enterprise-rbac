<?php

declare(strict_types=1);

namespace App\Enums;

enum DealStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case PendingApproval = 'pending_approval';
    case Closed = 'closed';

    public function label(): string
    {
        return ucwords(str_replace('_', ' ', $this->value));
    }
}

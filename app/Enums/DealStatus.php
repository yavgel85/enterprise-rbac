<?php

declare(strict_types=1);

namespace App\Enums;

enum DealStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Closed = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

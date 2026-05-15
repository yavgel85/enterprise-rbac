<?php

declare(strict_types=1);

namespace App\Enums;

enum ActivityType: string
{
    case Call = 'call';
    case Meeting = 'meeting';
    case Email = 'email';
    case Note = 'note';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

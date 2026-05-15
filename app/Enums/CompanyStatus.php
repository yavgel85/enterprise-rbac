<?php

declare(strict_types=1);

namespace App\Enums;

enum CompanyStatus: string
{
    case Lead = 'lead';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}

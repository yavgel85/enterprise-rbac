<?php

declare(strict_types=1);

namespace App\Enums;

enum DealStage: string
{
    case Lead = 'lead';
    case Qualified = 'qualified';
    case Proposal = 'proposal';
    case Negotiation = 'negotiation';
    case Won = 'won';
    case Lost = 'lost';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function isClosed(): bool
    {
        return $this === self::Won || $this === self::Lost;
    }
}

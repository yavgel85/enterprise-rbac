<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\CustomFieldValue;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasCustomFields
{
    public function customFieldValues(): MorphMany
    {
        return $this->morphMany(CustomFieldValue::class, 'owner');
    }

    /**
     * Typed value for a single custom field key (or null if unset).
     */
    public function cf(string $key): mixed
    {
        $value = $this->customFieldValues()
            ->with('definition')
            ->whereHas('definition', fn ($query) => $query->where('key', $key))
            ->first();

        return $value?->typedValue();
    }
}

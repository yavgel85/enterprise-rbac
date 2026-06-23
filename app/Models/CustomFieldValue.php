<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CustomFieldValue extends Model
{
    protected $fillable = [
        'definition_id',
        'owner_type',
        'owner_id',
        'value_text',
        'value_number',
        'value_date',
        'value_json',
    ];

    protected function casts(): array
    {
        return [
            'value_date' => 'date',
            'value_json' => 'array',
        ];
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(CustomFieldDefinition::class, 'definition_id');
    }

    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The typed value, resolved from whichever column the definition uses.
     */
    public function typedValue(): mixed
    {
        $type = $this->definition->type;
        $raw = $this->getAttribute($type->column());

        return $type->cast($raw);
    }
}

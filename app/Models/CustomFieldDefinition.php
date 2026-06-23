<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\CustomFieldType;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CustomFieldDefinition extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'model_type',
        'key',
        'label',
        'type',
        'options',
        'required',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'type' => CustomFieldType::class,
            'options' => 'array',
            'required' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class, 'definition_id');
    }

    /**
     * Definitions registered for a given owner model class, ordered for display.
     *
     * @param  class-string  $modelType
     */
    public function scopeForModel(Builder $query, string $modelType): Builder
    {
        return $query->where('model_type', $modelType)->orderBy('position')->orderBy('id');
    }
}

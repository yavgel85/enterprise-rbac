<?php

declare(strict_types=1);

namespace App\Actions\CustomField;

use App\Enums\CustomFieldType;
use App\Models\CustomFieldDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds validation rules from custom-field definitions and persists submitted
 * values into the EAV table (Improvement 5.3).
 */
final readonly class SyncCustomFields
{
    /**
     * Validation rules keyed by "custom_fields.{key}" for a given owner model.
     *
     * @param  class-string  $modelType
     * @return array<string, list<mixed>>
     */
    public function rules(string $modelType): array
    {
        $rules = [];

        foreach (CustomFieldDefinition::forModel($modelType)->get() as $definition) {
            $rules["custom_fields.{$definition->key}"] = $definition->type->rules(
                $definition->required,
                $definition->options ?? [],
            );
        }

        return $rules;
    }

    /**
     * Upsert submitted custom-field values for the owner model.
     *
     * @param  array<string, mixed>  $input  values keyed by definition key
     */
    public function persist(Model $owner, array $input): void
    {
        foreach (CustomFieldDefinition::forModel($owner::class)->get() as $definition) {
            $raw = $input[$definition->key] ?? null;

            $payload = [
                'value_text' => null,
                'value_number' => null,
                'value_date' => null,
                'value_json' => null,
            ];

            if ($definition->type === CustomFieldType::Boolean) {
                $payload['value_json'] = filter_var($raw, FILTER_VALIDATE_BOOLEAN);
            } elseif ($raw !== null && $raw !== '') {
                $payload[$definition->type->column()] = $raw;
            }

            $owner->customFieldValues()->updateOrCreate(
                ['definition_id' => $definition->id],
                $payload,
            );
        }
    }
}

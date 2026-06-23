<?php

declare(strict_types=1);

namespace App\Enums;

use Illuminate\Validation\Rule;

enum CustomFieldType: string
{
    case Text = 'text';
    case Number = 'number';
    case Date = 'date';
    case Select = 'select';
    case Boolean = 'boolean';
    case User = 'user';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function usesOptions(): bool
    {
        return $this === self::Select;
    }

    /**
     * Which physical column on custom_field_values stores this type.
     */
    public function column(): string
    {
        return match ($this) {
            self::Number, self::User => 'value_number',
            self::Date => 'value_date',
            self::Boolean => 'value_json',
            default => 'value_text',
        };
    }

    /**
     * Validation rules for a submitted value of this type.
     *
     * @param  list<string>  $options
     * @return list<mixed>
     */
    public function rules(bool $required, array $options = []): array
    {
        $base = match ($this) {
            self::Number => ['numeric'],
            self::Date => ['date'],
            self::Boolean => ['boolean'],
            self::User => ['integer', 'exists:users,id'],
            self::Select => [Rule::in($options)],
            default => ['string', 'max:1000'],
        };

        return [$required ? 'required' : 'nullable', ...$base];
    }

    /**
     * Normalise a raw stored value into a typed PHP value.
     */
    public function cast(mixed $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        return match ($this) {
            self::Number => $raw + 0,
            self::User => (int) $raw,
            self::Boolean => (bool) $raw,
            default => $raw,
        };
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;

/**
 * Whitelist mapping short keys to the CRM models that support custom fields
 * (Improvement 5.3). model_type is stored as the fully-qualified class name.
 */
final class CustomFieldModels
{
    /** @var array<string, class-string> */
    public const MAP = [
        'company' => Company::class,
        'contact' => Contact::class,
        'deal' => Deal::class,
        'task' => Task::class,
    ];

    public static function classFor(string $key): ?string
    {
        return self::MAP[$key] ?? null;
    }

    public static function keyFor(string $class): ?string
    {
        return array_search($class, self::MAP, true) ?: null;
    }

    /**
     * @return array<string, string> key => human label
     */
    public static function options(): array
    {
        $labels = [];
        foreach (self::MAP as $key => $class) {
            $labels[$key] = class_basename($class);
        }

        return $labels;
    }
}

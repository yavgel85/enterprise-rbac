<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\ActivityType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ActivityType::class)],
            'subject' => ['required', 'string', 'max:255'],
            'body' => ['nullable', 'string', 'max:10000'],
            'happened_at' => ['nullable', 'date'],
        ];
    }
}

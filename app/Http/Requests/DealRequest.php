<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Enums\DealStage;
use App\Enums\DealStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DealRequest extends FormRequest
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
            'company_id' => ['nullable', 'integer', 'exists:companies,id'],
            'contact_id' => ['nullable', 'integer', 'exists:contacts,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'currency' => ['required', 'string', 'size:3'],
            'stage' => ['required', Rule::enum(DealStage::class)],
            'status' => ['required', Rule::enum(DealStatus::class)],
            'probability' => ['required', 'integer', 'min:0', 'max:100'],
            'expected_close_date' => ['nullable', 'date'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            // Captured for win/loss analytics; only meaningful when stage = lost.
            'lost_reason' => ['nullable', 'string', 'max:255', Rule::requiredIf($this->input('stage') === DealStage::Lost->value)],
        ];
    }
}

<?php

namespace App\Http\Requests\Incident;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IncidentTrendRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'period' => [
                'nullable',
                Rule::in([
                    'daily',
                    'weekly',
                    'monthly',
                ]),
            ],
        ];
    }

    public function period(): string
    {
        return $this->input('period', 'daily');
    }
}
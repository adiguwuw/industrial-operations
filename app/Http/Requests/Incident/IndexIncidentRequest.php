<?php

namespace App\Http\Requests\Incident;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexIncidentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
     public function rules(): array
    {
        return [
            'status' => [
                'nullable',
                Rule::in([
                    'pending',
                    'investigating',
                    'resolved',
                ]),
            ],

            'severity' => [
                'nullable',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                    'critical',
                ]),
            ],

            'category_id' => [
                'nullable',
                'string',
                'exists:incident_categories,id',
            ],

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'sort' => [
                'nullable',
                Rule::in([
                    'reported_at',
                    '-reported_at',
                    'created_at',
                    '-created_at',
                    'severity',
                    '-severity',
                ]),
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}

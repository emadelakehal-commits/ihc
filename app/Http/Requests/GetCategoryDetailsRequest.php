<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCategoryDetailsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Add authorization logic if needed
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'category_codes' => 'required|array|min:1',
            'category_codes.*' => 'string|exists:lkp_category,category_code',
            'lang' => 'required|string|size:2|exists:lkp_language,code'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'category_codes.required' => 'Category codes array is required',
            'category_codes.array' => 'Category codes must be an array',
            'category_codes.min' => 'At least one category code is required',
            'category_codes.*.exists' => 'Invalid category code',
            'lang.required' => 'Language code is required',
            'lang.size' => 'Language code must be exactly 2 characters',
            'lang.exists' => 'Invalid language code',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'category_codes' => 'category codes',
            'category_codes.*' => 'category code',
            'lang' => 'language code',
        ];
    }
}

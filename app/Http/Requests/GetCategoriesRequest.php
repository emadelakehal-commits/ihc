<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetCategoriesRequest extends FormRequest
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
            'categories' => 'required|array|min:1',
            'categories.*' => 'string|exists:lkp_category,category_code',
            'lang' => 'required|string|size:2|exists:lkp_language,code'
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'categories.required' => 'Categories array is required',
            'categories.array' => 'Categories must be an array',
            'categories.min' => 'At least one category is required',
            'categories.*.exists' => 'Invalid category code',
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
            'categories' => 'categories',
            'categories.*' => 'category code',
            'lang' => 'language code',
        ];
    }
}

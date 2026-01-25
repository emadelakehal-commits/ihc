<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateProductRequest extends FormRequest
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
            'productCode' => 'required|string|max:100|unique:product,product_code',
            'translations' => 'required|array|min:1',
            'translations.*.language' => 'required|string|max:10',
            'translations.*.title' => 'required|string|max:255',
            'translations.*.summary' => 'nullable|string',
            'translations.*.description' => 'nullable|string',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'productCode.required' => 'Product code is required',
            'productCode.unique' => 'Product code already exists',
            'translations.required' => 'At least one translation is required',
            'translations.*.language.required' => 'Language is required for all translations',
            'translations.*.title.required' => 'Title is required for all translations',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'productCode' => 'product code',
            'translations.*.language' => 'language',
            'translations.*.title' => 'title',
            'translations.*.summary' => 'summary',
            'translations.*.description' => 'description',
        ];
    }
}

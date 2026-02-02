<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetProductDocumentsRequest extends FormRequest
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
            'product_code' => 'required|string|exists:product,product_code',
            'lang' => 'required|string|size:2|exists:lkp_language,code',
            'purpose' => 'nullable|string|in:manual,installation,specification,warranty,other',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'product_code.required' => 'Product code is required',
            'product_code.exists' => 'Invalid product code',
            'lang.required' => 'Language code is required',
            'lang.size' => 'Language code must be exactly 2 characters',
            'lang.exists' => 'Invalid language code',
            'purpose.in' => 'Purpose must be one of: manual, installation, specification, warranty, other',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'product_code' => 'product code',
            'lang' => 'language code',
            'purpose' => 'document purpose',
        ];
    }
}
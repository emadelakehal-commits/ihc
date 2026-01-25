<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
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
            'isActive' => 'sometimes|boolean',
            'cost' => 'sometimes|nullable|numeric|min:0',
            'costCurrency' => 'sometimes|nullable|string|size:3|exists:lkp_currency,code',
            'rrp' => 'sometimes|nullable|numeric|min:0',
            'rrpCurrency' => 'sometimes|nullable|string|size:3|exists:lkp_currency,code',
            'categories' => 'sometimes|array',
            'categories.*' => 'string|exists:lkp_category,category_code',
            'attributes' => 'sometimes|array',
            'attributes.*' => 'array', // Language code as key
            'attributes.*.*.name' => 'required|string|exists:lkp_attribute,name',
            'attributes.*.*.value' => 'required|string',
            'deliveries' => 'sometimes|array',
            'deliveries.*.min' => 'required|integer|min:0',
            'deliveries.*.max' => 'required|integer|min:0|gte:deliveries.*.min',
            'documents' => 'sometimes|array',
            'documents.*.type' => 'required|string|in:manual,technical,warranty',
            'documents.*.url' => 'required|string|max:500',
            'tags' => 'sometimes|array',
            'tags.*' => 'string|exists:lkp_tag,tag_code',
            'itemTags' => 'sometimes|array',
            'itemTags.*' => 'string|exists:lkp_item_tag,item_tag_code',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'isActive.boolean' => 'Active status must be true or false',
            'cost.numeric' => 'Cost must be a number',
            'cost.min' => 'Cost cannot be negative',
            'rrp.numeric' => 'RRP must be a number',
            'rrp.min' => 'RRP cannot be negative',
            'categories.*.exists' => 'Invalid category code',
            'attributes.*.*.name.exists' => 'Invalid attribute name',
            'tags.*.exists' => 'Invalid tag code',
            'itemTags.*.exists' => 'Invalid item tag code',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'isActive' => 'active status',
            'cost' => 'cost',
            'costCurrency' => 'cost currency',
            'rrp' => 'recommended retail price',
            'rrpCurrency' => 'RRP currency',
            'categories' => 'categories',
            'attributes' => 'attributes',
            'deliveries' => 'deliveries',
            'documents' => 'documents',
            'tags' => 'tags',
            'itemTags' => 'item tags',
        ];
    }
}

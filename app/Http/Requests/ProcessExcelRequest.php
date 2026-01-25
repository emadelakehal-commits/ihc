<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessExcelRequest extends FormRequest
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
            'file' => 'required|string|max:255',
            'lang' => 'required|string|size:2|exists:lkp_language,code',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Excel file name is required',
            'file.string' => 'File name must be a string',
            'file.max' => 'File name cannot exceed 255 characters',
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
            'file' => 'Excel file name',
            'lang' => 'language code',
        ];
    }

    /**
     * Get data to be validated from the request.
     * For GET requests, use query parameters instead of request body.
     */
    public function validationData(): array
    {
        if ($this->isMethod('get')) {
            return $this->query();
        }

        return parent::validationData();
    }
}

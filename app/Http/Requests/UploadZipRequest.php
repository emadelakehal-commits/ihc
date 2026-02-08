<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UploadZipRequest extends FormRequest
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
            'zip_file' => 'required|file|mimes:zip|max:102400', // 100MB max
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'zip_file.required' => 'A zip file is required',
            'zip_file.file' => 'The uploaded file must be a valid file',
            'zip_file.mimes' => 'The file must be a zip archive',
            'zip_file.max' => 'The zip file must not exceed 100MB',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'zip_file' => 'zip file',
        ];
    }
}
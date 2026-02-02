<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductDocumentsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        // Handle both single object and array cases
        if (is_array($this->resource)) {
            return $this->resource;
        }
        
        return [
            'success' => true,
            'message' => 'Product documents retrieved successfully',
            'data' => [
                'product_code' => $this->product_code,
                'language' => $this->language,
                'purpose' => $this->purpose,
                'documents' => $this->documents,
            ]
        ];
    }
}
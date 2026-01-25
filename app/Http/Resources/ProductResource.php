<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Product created successfully',
            'data' => [
                'product_code' => $this->product_code,
                'created_at' => $this->created_at,
                'updated_at' => $this->updated_at,
                'translations' => $this->translations->map(function ($translation) {
                    return [
                        'language' => $translation->language,
                        'title' => $translation->title,
                        'summary' => $translation->summary,
                        'description' => $translation->description,
                    ];
                }),
            ]
        ];
    }
}

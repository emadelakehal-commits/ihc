<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDocument extends Model
{
    protected $table = 'product_document';
    public $timestamps = false;

    protected $fillable = ['product_code', 'doc_type', 'file_url', 'created_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_item_code'); // Updated to reference product_item_code
    }
}

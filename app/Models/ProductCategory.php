<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    protected $table = 'product_category';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['product_code', 'category_code'];
}

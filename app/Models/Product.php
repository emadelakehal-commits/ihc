<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'product_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['product_code'];

    public function translations()
    {
        return $this->hasMany(ProductTranslation::class, 'product_code', 'product_code');
    }

    public function productItems()
    {
        return $this->hasMany(ProductItem::class, 'product_code', 'product_code');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'product_tag', 'product_code', 'tag_code', 'product_code', 'tag_code');
    }
}

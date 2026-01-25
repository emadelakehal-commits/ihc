<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttributeValue extends Model
{
    protected $table = 'product_attribute_value';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['isku', 'attribute_name', 'language', 'value'];

    protected $primaryKey = ['isku', 'attribute_name', 'language'];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'isku', 'isku');
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_name', 'name');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

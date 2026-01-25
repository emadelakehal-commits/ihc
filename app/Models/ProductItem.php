<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItem extends Model
{
    protected $table = 'product_item';
    protected $primaryKey = 'isku';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['product_item_code', 'isku', 'product_code', 'is_active', 'cost', 'cost_currency', 'rrp', 'rrp_currency', 'availability'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_code');
    }

    public function itemTags()
    {
        return $this->belongsToMany(ItemTag::class, 'product_item_tag', 'isku', 'item_tag_code', 'isku', 'item_tag_code');
    }

    public function translations()
    {
        return $this->hasMany(ProductItemTranslation::class, 'isku', 'isku');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category', 'product_code', 'category_code', 'isku', 'category_code');
    }

    public function attributeValues()
    {
        return $this->hasMany(ProductAttributeValue::class, 'isku', 'isku');
    }

    public function deliveries()
    {
        return $this->hasMany(ProductDelivery::class, 'isku', 'isku');
    }

    public function documents()
    {
        return $this->hasMany(ProductDocument::class, 'product_code', 'isku');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTag extends Model
{
    protected $table = 'lkp_item_tag';
    protected $primaryKey = 'item_tag_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['item_tag_code'];

    public function translations()
    {
        return $this->hasMany(ItemTagTranslation::class, 'item_tag_code', 'item_tag_code');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_item_tag', 'item_tag_code', 'product_item_code', 'item_tag_code', 'product_item_code');
    }
}

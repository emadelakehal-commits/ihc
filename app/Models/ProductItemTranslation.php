<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductItemTranslation extends Model
{
    protected $table = 'product_item_translation';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = ['isku', 'language', 'title', 'short_desc'];

    protected $primaryKey = ['isku', 'language'];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'isku', 'isku');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

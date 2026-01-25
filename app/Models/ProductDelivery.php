<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductDelivery extends Model
{
    protected $table = 'product_delivery';
    protected $primaryKey = 'isku';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['isku', 'domain_id', 'delivery_min', 'delivery_max'];

    public function productItem()
    {
        return $this->belongsTo(ProductItem::class, 'isku', 'isku');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id', 'code');
    }
}

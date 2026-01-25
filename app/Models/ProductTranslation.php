<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductTranslation extends Model
{
    protected $table = 'product_translation';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['product_code', 'language', 'title', 'summary', 'description'];

    protected $primaryKey = ['product_code', 'language'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_code', 'product_code');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

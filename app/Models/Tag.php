<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    protected $table = 'lkp_tag';
    protected $primaryKey = 'tag_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['tag_code'];

    public function translations()
    {
        return $this->hasMany(TagTranslation::class, 'tag_code', 'tag_code');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tag', 'tag_code', 'product_item_code', 'tag_code', 'product_item_code');
    }
}

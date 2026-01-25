<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItemTagTranslation extends Model
{
    protected $table = 'lkp_item_tag_translation';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = ['item_tag_code', 'language', 'name'];

    protected $primaryKey = ['item_tag_code', 'language'];

    public function itemTag()
    {
        return $this->belongsTo(ItemTag::class, 'item_tag_code', 'item_tag_code');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

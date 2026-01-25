<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TagTranslation extends Model
{
    protected $table = 'lkp_tag_translation';
    public $incrementing = false;
    public $timestamps = true;

    protected $fillable = ['tag_code', 'language', 'name'];

    protected $primaryKey = ['tag_code', 'language'];

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_code', 'tag_code');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

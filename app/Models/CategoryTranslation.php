<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategoryTranslation extends Model
{
    protected $table = 'lkp_category_translation';
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = ['category_code', 'language', 'name'];

    protected $primaryKey = ['category_code', 'language'];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_code', 'category_code');
    }

    public function language()
    {
        return $this->belongsTo(Language::class, 'language', 'code');
    }
}

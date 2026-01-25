<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'lkp_category';
    protected $primaryKey = 'category_code';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['category_code'];

    // Many-to-many relationship: a category can have multiple parents
    public function parents()
    {
        return $this->belongsToMany(
            Category::class,
            'category_hierarchy',
            'category_code',
            'parent_code'
        );
    }

    // Many-to-many relationship: a category can have multiple children
    public function children()
    {
        return $this->belongsToMany(
            Category::class,
            'category_hierarchy',
            'parent_code',
            'category_code'
        );
    }

    public function translations()
    {
        return $this->hasMany(CategoryTranslation::class, 'category_code', 'category_code');
    }
}

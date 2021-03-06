<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['parent', 'name', 'slug', 'description'];
    
    public function posts()
    {
        return $this->belongsToMany(Post::class);
    }
}

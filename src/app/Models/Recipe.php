<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Recipe extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'ingredients',
        'body',
        'image_path',
        'admin_id',
    ];


    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

}

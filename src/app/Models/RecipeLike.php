<?php

// app/Models/RecipeLike.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Recipe;

class RecipeLike extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipe_id',
    ];

    // ==================== Relationships ====================

    /**
     * いいねしたユーザー
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * いいねされたレシピ
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    // ==================== Events ====================

    /**
     * いいね作成時にレシピのいいね数を更新
     */
    protected static function booted()
    {
        static::created(function ($like) {
            $like->recipe->updateLikesCount();
        });

        static::deleted(function ($like) {
            $like->recipe->updateLikesCount();
        });
    }
}


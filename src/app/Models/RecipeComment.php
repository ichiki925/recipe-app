<?php

// app/Models/RecipeComment.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\Recipe;

class RecipeComment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'content',
        'user_id',
        'recipe_id',
    ];

    protected $dates = [
        'deleted_at',
    ];

    // ==================== Relationships ====================

    /**
     * コメント投稿者
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * コメント対象のレシピ
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    // ==================== Accessors ====================

    /**
     * コメント日時を日本語形式で取得
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('Y年m月d日 H:i');
    }

    // ==================== Scopes ====================

    /**
     * 最新順
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 特定レシピのコメント
     */
    public function scopeForRecipe($query, $recipeId)
    {
        return $query->where('recipe_id', $recipeId);
    }
}
<?php

// app/Models/Recipe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'genre',
        'servings',
        'ingredients',
        'instructions',
        'image_url',
        'admin_id',
        'is_published',
        'views_count',
        'likes_count',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected $dates = [
        'deleted_at',
    ];

    // ==================== Relationships ====================

    /**
     * レシピの投稿者（管理者）
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    /**
     * レシピへのいいね
     */
    public function likes()
    {
        return $this->hasMany(RecipeLike::class);
    }

    /**
     * いいねしたユーザー
     */
    public function likedByUsers()
    {
        return $this->belongsToMany(User::class, 'recipe_likes')->withTimestamps();
    }

    /**
     * レシピへのコメント
     */
    public function comments()
    {
        return $this->hasMany(RecipeComment::class)->orderBy('created_at', 'desc');
    }

    // ==================== Accessors & Mutators ====================

    /**
     * 画像URL取得（デフォルト画像対応）
     */
    public function getImageAttribute()
    {
        return $this->image_url ?: '/images/no-image.png';
    }

    /**
     * 材料を配列に変換
     */
    public function getIngredientsArrayAttribute()
    {
        return array_filter(array_map('trim', explode("\n", $this->ingredients)));
    }

    /**
     * 作り方を配列に変換
     */
    public function getInstructionsArrayAttribute()
    {
        return array_filter(array_map('trim', explode("\n", $this->instructions)));
    }

    // ==================== Scopes ====================

    /**
     * 公開済みレシピのみ
     */
    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    /**
     * 人気順（いいね数降順）
     */
    public function scopePopular($query)
    {
        return $query->orderBy('likes_count', 'desc');
    }

    /**
     * 最新順
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * 検索（タイトル、材料、ジャンル）
     */
    public function scopeSearch($query, $keyword)
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('title', 'LIKE', "%{$keyword}%")
              ->orWhere('ingredients', 'LIKE', "%{$keyword}%")
              ->orWhere('genre', 'LIKE', "%{$keyword}%");
        });
    }

    /**
     * ジャンル別
     */
    public function scopeByGenre($query, $genre)
    {
        if (empty($genre)) {
            return $query;
        }

        return $query->where('genre', $genre);
    }

    // ==================== Methods ====================

    /**
     * いいね数を更新
     */
    public function updateLikesCount()
    {
        $this->likes_count = $this->likes()->count();
        $this->save();
    }

    /**
     * 閲覧数を増加
     */
    public function incrementViews()
    {
        $this->increment('views_count');
    }

    /**
     * ユーザーがいいね済みかチェック
     */
    public function isLikedBy($user)
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }
}

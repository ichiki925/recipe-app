<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Recipe;
use App\Models\RecipeLike;
use App\Models\RecipeComment;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'firebase_uid',
        'name',
        'email',
        'username',
        'avatar_url',
        'role',
        'email_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    // ==================== Relationships ====================

    /**
     * 管理者が投稿したレシピ
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'admin_id');
    }

    /**
     * ユーザーがいいねしたレシピ
     */
    public function likedRecipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_likes')->withTimestamps();
    }

    /**
     * ユーザーのいいね一覧
     */
    public function recipeLikes()
    {
        return $this->hasMany(RecipeLike::class);
    }

    /**
     * ユーザーのコメント一覧
     */
    public function recipeComments()
    {
        return $this->hasMany(RecipeComment::class);
    }

    // ==================== Role Methods ====================

    /**
     * 管理者判定
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * 一般ユーザー判定
     */
    public function isUser()
    {
        return $this->role === 'user';
    }

    // ==================== Accessors & Mutators ====================

    /**
     * 管理者判定
     */
    public function getIsAdminAttribute()
    {
        return $this->isAdmin();
    }

    /**
     * ユーザー判定（Accessor版 - 後方互換性のため）
     */
    public function getIsUserAttribute()
    {
        return $this->isUser();
    }

    /**
     * アバター画像URL取得（デフォルト画像対応）
     */
    public function getAvatarAttribute()
    {
        return $this->avatar_url ?: '/images/default-avatar.png';
    }

    /**
     * 🔧 アバター画像URLアクセサー（重要）
     * これにより avatar_url フィールドが正しく処理されます
     */
    public function getAvatarUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // すでに完全なURLの場合
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        // /storage/ で始まる場合はそのまま
        if (str_starts_with($value, '/storage/')) {
            return $value;
        }

        // それ以外は /storage/ を付ける
        return '/storage/' . $value;
    }

    /**
     * 🔧 特定のレシピにいいねしているかチェック
     */
    public function hasLikedRecipe($recipeId): bool
    {
        return $this->recipeLikes()->where('recipe_id', $recipeId)->exists();
    }



    // ==================== Scopes ====================

    /**
     * 管理者のみ取得
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    /**
     * 一般ユーザーのみ取得
     */
    public function scopeUsers($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Firebase UIDで検索
     */
    public function scopeByFirebaseUid($query, $firebaseUid)
    {
        return $query->where('firebase_uid', $firebaseUid);
    }

    /**
     * ユーザーのコメント一覧（エイリアス）
     */
    public function comments()
    {
        return $this->recipeComments();
    }

    /**
     * ユーザーのいいね一覧（エイリアス）
     */
    public function likes()
    {
        return $this->recipeLikes();
    }


}

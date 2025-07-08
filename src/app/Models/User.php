<?php

// app/Models/User.php
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

    // ==================== Accessors & Mutators ====================

    /**
     * 管理者判定
     */
    public function getIsAdminAttribute()
    {
        return $this->role === 'admin';
    }

    /**
     * アバター画像URL取得（デフォルト画像対応）
     */
    public function getAvatarAttribute()
    {
        return $this->avatar_url ?: '/images/default-avatar.png';
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
}

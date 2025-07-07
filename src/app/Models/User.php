<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'firebase_uid',  // Firebase認証用
        'name',
        'email',
        'avatar',        // プロフィール画像
        'role',          // admin or user
        'email_verified_at',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * 管理者かどうかを判定
     */
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    /**
     * ユーザーかどうかを判定
     */
    public function isUser()
    {
        return $this->role === 'user';
    }

    /**
     * レシピとのリレーション（管理者が投稿したレシピ）
     */
    public function recipes()
    {
        return $this->hasMany(Recipe::class, 'admin_id');
    }

    /**
     * お気に入りレシピとのリレーション
     */
    public function favorites()
    {
        return $this->belongsToMany(Recipe::class, 'favorites')->withTimestamps();
    }

    /**
     * コメントとのリレーション
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
}
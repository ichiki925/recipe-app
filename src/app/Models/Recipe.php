<?php

// app/Models/Recipe.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;
use App\Support\JaString;


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
        'search_reading',
    ];

    protected $appends = ['image_full_url'];


    protected $casts = [
        'is_published' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $dates = [
        'deleted_at',
    ];

    // リレーション

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
    public function getImageFullUrlAttribute(): string
    {
        // DBに '/storage/recipe_images/foo.jpg' が入っている想定
        $value = $this->attributes['image_url'] ?? '';

        // 値がなければ即フォールバック
        if (!$value) {
            return url('/images/no-image.png');
        }

        // '/storage/...' → 'recipe_images/...'
        $path = ltrim(str_replace('/storage/', '', $value), '/');

        // 物理ファイルがあるかを public ディスクで確認
        if (Storage::disk('public')->exists($path)) {
            // Storage::url($path) は '/storage/...' を返すので absolute に
            return url(Storage::url($path));
        }

        // ファイルが無いときは既定画像
        return url('/images/no-image.png');
    }

    /**
     * 🔧 いいね数を取得（リアルタイム計算 + キャッシュ併用）
     */
    public function getLikesCountAttribute($value)
    {
        // DBのlikes_countカラムがnullまたは0の場合のみリアルタイム計算
        if (is_null($value) || $value === 0) {
            return $this->likes()->count();
        }

        // それ以外はDBの値を使用（パフォーマンス重視）
        return $value;
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
        if (empty($keyword)) return $query;

        $raw  = $keyword;
        $like = '%' . \App\Support\JaString::normalizeToHiragana($raw) . '%';

        return $query->where(function ($q) use ($raw, $like) {
            $q->where('title', 'LIKE', "%{$raw}%")
            ->orWhere('genre', 'LIKE', "%{$raw}%")
            ->orWhere('ingredients', 'LIKE', "%{$raw}%")
            ->orWhere('instructions', 'LIKE', "%{$raw}%")  // この行を追加
            ->orWhere('search_reading', 'LIKE', $like);
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

    protected static function booted()
    {
        static::saving(function (\App\Models\Recipe $recipe) {
            $plain = trim(
                ($recipe->title ?? '') . ' ' .
                ($recipe->genre ?? '') . ' ' .
                ($recipe->ingredients ?? '') . ' ' .
                ($recipe->instructions ?? '')  // この行を追加
            );

            $hira = \App\Support\JaString::normalizeToHiragana($plain);
            $recipe->search_reading = trim($hira . ' ' . $plain);
        });

    }

    // ==================== Methods ====================

    public function updateLikesCount()
    {
        $count = $this->likes()->count();
        $this->update(['likes_count' => $count]);
        return $count;
    }

    public function incrementViews()
    {
        $this->increment('views_count');
    }

    public function isLikedBy($user)
    {
        if (!$user) {
            return false;
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function refreshLikesCount()
    {
        $this->likes_count = $this->likes()->count();
        $this->saveQuietly(); // イベントを発火させずに保存
        return $this->likes_count;
    }

}

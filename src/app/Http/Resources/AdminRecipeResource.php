<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AdminRecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'genre' => $this->genre,
            'servings' => $this->servings,
            'ingredients' => $this->ingredients,
            'instructions' => $this->instructions,
            'ingredients_array' => $this->ingredients_array,
            'instructions_array' => $this->instructions_array,
            'image_url' => $this->image_url,
            'image' => $this->image, // デフォルト画像対応
            'is_published' => $this->is_published,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments()->count(),
            
            // 管理者情報
            'admin' => [
                'id' => $this->admin->id,
                'name' => $this->admin->name,
                'email' => $this->admin->email,
            ],
            
            // ステータス関連
            'status' => [
                'text' => $this->is_published ? '公開中' : '下書き',
                'class' => $this->is_published ? 'published' : 'draft',
                'color' => $this->is_published ? 'success' : 'warning',
            ],
            
            // 削除情報（ソフトデリート対応）
            'is_deleted' => $this->trashed(),
            'deleted_at' => $this->deleted_at ? $this->deleted_at->format('Y-m-d H:i:s') : null,
            'deleted_at_human' => $this->deleted_at ? $this->deleted_at->diffForHumans() : null,
            
            // 日時情報
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
            'created_at_human' => $this->created_at->diffForHumans(),
            'updated_at_human' => $this->updated_at->diffForHumans(),
            'created_at_formatted' => $this->created_at->format('Y年m月d日 H:i'),
            'updated_at_formatted' => $this->updated_at->format('Y年m月d日 H:i'),
            
            // 統計情報
            'stats' => [
                'total_interactions' => $this->likes_count + $this->comments()->count(),
                'engagement_rate' => $this->views_count > 0 ? 
                    round(($this->likes_count + $this->comments()->count()) / $this->views_count * 100, 2) : 0,
                'likes_per_view' => $this->views_count > 0 ? 
                    round($this->likes_count / $this->views_count * 100, 2) : 0,
            ],
            
            // 最新のコメント情報（管理用）
            'latest_comments' => $this->whenLoaded('comments', function () {
                return $this->comments->take(3)->map(function ($comment) {
                    return [
                        'id' => $comment->id,
                        'content' => $comment->content,
                        'user_name' => $comment->user->name ?? '不明',
                        'created_at_human' => $comment->created_at->diffForHumans(),
                    ];
                });
            }),
            
            // いいねユーザー情報（管理用）
            'recent_likes' => $this->whenLoaded('likes', function () {
                return $this->likes->take(5)->map(function ($like) {
                    return [
                        'id' => $like->id,
                        'user_name' => $like->user->name ?? '不明',
                        'created_at_human' => $like->created_at->diffForHumans(),
                    ];
                });
            }),
            
            // 管理者向けアクション用URL
            'urls' => [
                'show' => "/admin/recipes/{$this->id}",
                'edit' => "/admin/recipes/{$this->id}/edit",
                'public_view' => "/recipes/{$this->id}",
            ],
        ];
    }
}
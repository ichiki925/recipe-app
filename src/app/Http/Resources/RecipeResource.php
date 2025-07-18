<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeResource extends JsonResource
{
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
            'image' => $this->image,
            'views_count' => $this->views_count,
            'likes_count' => $this->likes_count,
            'is_published' => $this->is_published,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'formatted_created_at' => $this->created_at->format('Y年m月d日'),

            // レシピ作成者情報（管理者）
            'admin' => new UserResource($this->whenLoaded('admin')),

            // コメント情報
            'comments' => CommentResource::collection($this->whenLoaded('comments')),
            'comments_count' => $this->when($this->relationLoaded('comments'), function () {
                return $this->comments->count();
            }),

            // 認証ユーザー用の情報
            'is_liked' => $this->when($request->user(), function () use ($request) {
                return $this->isLikedBy($request->user());
            }),

            // 閲覧数増加は自動的に行われる（モデルのincrementViews()メソッド）
        ];
    }
}
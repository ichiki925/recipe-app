<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'content' => $this->content,
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'formatted_date' => $this->created_at->format('Y年m月d日 H:i'),
            'time_ago' => $this->created_at->diffForHumans(),

            // コメント投稿者情報
            'user' => new UserResource($this->whenLoaded('user')),

            // レシピ情報（必要に応じて）
            'recipe' => $this->when($this->relationLoaded('recipe'), [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
            ]),

            // 削除日時（管理者用）
            'deleted_at' => $this->when($request->user() && $request->user()->isAdmin(), $this->deleted_at),
        ];
    }
}
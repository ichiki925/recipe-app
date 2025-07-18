<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RecipeLikeResource extends JsonResource
{

    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'created_at' => $this->created_at->toISOString(),
            'formatted_created_at' => $this->created_at->format('Y年m月d日 H:i'),

            // いいねしたユーザー情報
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url,
            ],

            // いいねされたレシピ情報（最小限）
            'recipe' => [
                'id' => $this->recipe->id,
                'title' => $this->recipe->title,
                'image' => $this->recipe->image_url,
            ],

            // 管理者用の詳細情報
            'user_details' => $this->when($request->user() && $request->user()->isAdmin(), [
                'email' => $this->user->email,
                'username' => $this->user->username,
                'user_created_at' => $this->user->created_at,
            ]),
        ];
    }
}


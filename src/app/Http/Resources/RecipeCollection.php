<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RecipeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'data' => $this->collection->map(function ($recipe) use ($request) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'servings' => $recipe->servings,
                    'image_url' => $recipe->image_url ?? '/images/no-image.png',
                    'views_count' => $recipe->views_count ?? 0,
                    'likes_count' => $recipe->likes_count ?? 0,
                    'created_at' => $recipe->created_at->toISOString(),
                    'formatted_created_at' => $recipe->created_at->format('Y年m月d日'),

                    'admin' => $recipe->admin ? [
                        'id' => $recipe->admin->id,
                        'name' => $recipe->admin->name,
                        'avatar' => $recipe->admin->avatar ?? '/images/default-avatar.png',
                    ] : null,

                    // 認証ユーザーのいいね状態
                    'is_liked' => $request->user() ? $recipe->isLikedBy($request->user()) : false,
                ];
            }),

            // ページネーション情報
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],

            // 統計情報
            'stats' => [
                'total_recipes' => $this->total(),
                'showing' => $this->count(),
            ],

            // ソート・フィルタ情報
            'filters' => [
                'genre' => $request->get('genre'),
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'latest'),
            ],
        ];
    }
}
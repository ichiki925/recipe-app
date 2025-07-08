<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeLike;
use Illuminate\Http\Request;

class LikeController extends Controller
{
    public function store(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        // 管理者はいいねできない
        if ($user->isAdmin()) {
            return response()->json([
                'message' => '管理者はいいねできません'
            ], 403);
        }

        // 公開されていないレシピにはいいねできない
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        // 既にいいね済みかチェック
        $existingLike = RecipeLike::where('user_id', $user->id)
                                ->where('recipe_id', $recipe->id)
                                ->first();

        if ($existingLike) {
            return response()->json([
                'message' => '既にいいね済みです',
                'is_liked' => true,
                'likes_count' => $recipe->likes_count
            ], 200);
        }

        // いいね作成
        RecipeLike::create([
            'user_id' => $user->id,
            'recipe_id' => $recipe->id
        ]);

        // レシピのいいね数を更新（Model のイベントで自動実行）
        $recipe->refresh(); // 最新のいいね数を取得

        return response()->json([
            'message' => 'いいねしました',
            'is_liked' => true,
            'likes_count' => $recipe->likes_count
        ], 201);
    }

    public function destroy(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        // いいねを検索
        $like = RecipeLike::where('user_id', $user->id)
                        ->where('recipe_id', $recipe->id)
                        ->first();

        if (!$like) {
            return response()->json([
                'message' => 'いいねしていません',
                'is_liked' => false,
                'likes_count' => $recipe->likes_count
            ], 200);
        }

        // いいね削除
        $like->delete();

        // レシピのいいね数を更新（Model のイベントで自動実行）
        $recipe->refresh(); // 最新のいいね数を取得

        return response()->json([
            'message' => 'いいねを取り消しました',
            'is_liked' => false,
            'likes_count' => $recipe->likes_count
        ], 200);
    }

    public function index(Recipe $recipe)
    {
        $likes = $recipe->likes()
                    ->with('user:id,name,username,avatar_url')
                    ->latest()
                    ->paginate(20);

        return response()->json([
            'data' => $likes,
            'total_likes' => $recipe->likes_count
        ]);
    }

    public function userLikes(Request $request)
    {
        $user = auth()->user();

        $likedRecipes = $user->likedRecipes()
                        ->published()
                        ->with('admin')
                        ->latest('recipe_likes.created_at')
                        ->paginate(6);

        // 各レシピにいいね状態を追加
        $likedRecipes->getCollection()->transform(function ($recipe) {
            $recipe->is_liked = true; // 全部いいね済み
            return $recipe;
        });

        return response()->json($likedRecipes);
    }

    public function stats()
    {
        // 管理者のみアクセス可能（ルートでミドルウェア設定）

        $stats = [
            'total_likes' => RecipeLike::count(),
            'today_likes' => RecipeLike::whereDate('created_at', today())->count(),
            'this_week_likes' => RecipeLike::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month_likes' => RecipeLike::whereMonth('created_at', now()->month)
                                        ->whereYear('created_at', now()->year)
                                        ->count(),
        ];

        // 人気レシピ Top 5
        $popularRecipes = Recipe::published()
                            ->orderBy('likes_count', 'desc')
                            ->take(5)
                            ->get(['id', 'title', 'likes_count']);

        // 最近いいねが多いユーザー Top 5
        $activeUsers = RecipeLike::selectRaw('user_id, COUNT(*) as likes_given')
                                ->with('user:id,name,username')
                                ->whereBetween('created_at', [now()->subDays(30), now()])
                                ->groupBy('user_id')
                                ->orderBy('likes_given', 'desc')
                                ->take(5)
                                ->get();

        return response()->json([
            'stats' => $stats,
            'popular_recipes' => $popularRecipes,
            'active_users' => $activeUsers
        ]);
    }

    public function toggle(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        // 管理者はいいねできない
        if ($user->isAdmin()) {
            return response()->json([
                'message' => '管理者はいいねできません'
            ], 403);
        }

        // 公開されていないレシピにはいいねできない
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        $like = RecipeLike::where('user_id', $user->id)
                        ->where('recipe_id', $recipe->id)
                        ->first();

        if ($like) {
            // いいね済み → 削除
            $like->delete();
            $recipe->refresh();

            return response()->json([
                'message' => 'いいねを取り消しました',
                'is_liked' => false,
                'likes_count' => $recipe->likes_count
            ]);
        } else {
            // 未いいね → 追加
            RecipeLike::create([
                'user_id' => $user->id,
                'recipe_id' => $recipe->id
            ]);
            $recipe->refresh();

            return response()->json([
                'message' => 'いいねしました',
                'is_liked' => true,
                'likes_count' => $recipe->likes_count
            ]);
        }
    }

}

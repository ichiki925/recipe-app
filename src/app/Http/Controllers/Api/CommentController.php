<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeComment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * レシピのコメント一覧取得
     * GET /api/recipes/{recipe}/comments
     */

    public function index(Recipe $recipe)
    {
        // 公開されていないレシピのコメントは取得不可
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        $comments = $recipe->comments()
                        ->with(['user:id,name,username,avatar_url'])
                        ->latest()
                        ->paginate(20);

        // コメント数も含めて返す
        return response()->json([
            'data' => $comments->items(),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
            'total_comments' => $comments->total()
        ]);
    }

    /**
     * コメント投稿
     * POST /api/recipes/{recipe}/comments
     */
    public function store(Request $request, Recipe $recipe)
    {
        $user = auth()->user();

        // 管理者はコメント投稿不可
        if ($user->isAdmin()) {
            return response()->json([
                'message' => '管理者はコメントできません'
            ], 403);
        }

        // 公開されていないレシピにはコメント不可
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        $request->validate([
            'content' => 'required|string|min:1|max:500'
        ], [
            'content.required' => 'コメント内容は必須です',
            'content.min' => 'コメントは1文字以上で入力してください',
            'content.max' => 'コメントは500文字以内で入力してください'
        ]);

        // スパム対策：同じユーザーが短時間で連続投稿を防ぐ
        $recentComment = RecipeComment::where('user_id', $user->id)
                                    ->where('recipe_id', $recipe->id)
                                    ->where('created_at', '>=', now()->subMinutes(1))
                                    ->first();

        if ($recentComment) {
            return response()->json([
                'message' => '1分以内の連続投稿はできません'
            ], 429);
        }

        $comment = RecipeComment::create([
            'content' => trim($request->content),
            'user_id' => $user->id,
            'recipe_id' => $recipe->id
        ]);

        // 作成したコメントをユーザー情報と一緒に返す
        $comment->load('user:id,name,username,avatar_url');

        return response()->json([
            'message' => 'コメントを投稿しました',
            'data' => $comment
        ], 201);
    }

    /**
     * コメント詳細取得（管理者用・デバッグ用）
     * GET /api/comments/{comment}
     */
    public function show(RecipeComment $comment)
    {
        $comment->load(['user:id,name,username,avatar_url,email', 'recipe:id,title']);

        return response()->json([
            'data' => $comment
        ]);
    }

    /**
     * ユーザーが投稿したコメント一覧
     * GET /api/user/comments
     */
    public function userComments(Request $request)
    {
        $user = auth()->user();

        $comments = $user->recipeComments()
                        ->with(['recipe:id,title,image_url'])
                        ->latest()
                        ->paginate(20);

        return response()->json($comments);
    }

}

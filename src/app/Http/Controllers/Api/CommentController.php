<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\RecipeComment;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;

class CommentController extends Controller
{
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
            'data' => CommentResource::collection($comments),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
            'total_comments' => $comments->total()
        ]);
    }

    public function store(Request $request, Recipe $recipe)
    {
        try {
            \Log::info('=== Comment Store START ===', [
                'request_content' => $request->input('content'),
                'recipe_id' => $recipe->id,
            ]);

            $user = $request->user();

            if (!$user) {
                return response()->json(['message' => '認証が必要です'], 401);
            }

            if ($user->isAdmin()) {
                return response()->json(['message' => '管理者はコメントできません'], 403);
            }

            if (!$recipe->is_published) {
                return response()->json(['message' => 'このレシピは公開されていません'], 404);
            }

            $request->validate([
                'content' => 'required|string|min:1|max:500'
            ]);

            // 連続投稿制限（1分以内）
            $recentComment = RecipeComment::where('user_id', $user->id)
                ->where('recipe_id', $recipe->id)
                ->where('created_at', '>=', now()->subMinute())
                ->first();

            if ($recentComment) {
                return response()->json(['message' => '1分以内の連続投稿はできません'], 429);
            }

            // コメント作成
            $comment = RecipeComment::create([
                'content' => trim($request->content),
                'user_id' => $user->id,
                'recipe_id' => $recipe->id,
            ]);

            \Log::info('Comment created successfully', ['comment_id' => $comment->id]);

            // 🔧 userリレーションを事前にロード（Resourceで使うため）
            $comment->load('user');

            // ✅ Resourceで整形して返却
            return response()->json([
                'message' => 'コメントを投稿しました',
                'data' => new CommentResource($comment)
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Comment creation failed', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'message' => 'コメントの投稿に失敗しました'
            ], 500);
        }
    }

    /**
     * コメント削除（管理者専用）
     * DELETE /api/comments/{comment}
     */
    public function destroy(RecipeComment $comment)
    {
        $user = auth()->user();

        // 管理者のみ削除可能
        if (!$user->isAdmin()) {
            return response()->json([
                'message' => 'このコメントを削除する権限がありません'
            ], 403);
        }

        $comment->delete();

        return response()->json([
            'message' => 'コメントを削除しました'
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

        return response()->json([
            'data' => CommentResource::collection($comments),
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ]
        ]);
    }

}

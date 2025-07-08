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

    /**
     * レシピ別コメント統計（管理者用）
     * GET /api/admin/comment-stats
     */
    public function stats()
    {
        $stats = [
            'total_comments' => RecipeComment::count(),
            'today_comments' => RecipeComment::whereDate('created_at', today())->count(),
            'this_week_comments' => RecipeComment::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month_comments' => RecipeComment::whereMonth('created_at', now()->month)
                                                ->whereYear('created_at', now()->year)
                                                ->count(),
        ];

        // コメント数の多いレシピ Top 5
        $topCommentedRecipes = Recipe::withCount('comments')
                                ->orderBy('comments_count', 'desc')
                                ->take(5)
                                ->get(['id', 'title', 'comments_count']);

        // アクティブなコメント投稿者 Top 5
        $activeCommenters = RecipeComment::selectRaw('user_id, COUNT(*) as comments_count')
                                    ->with('user:id,name,username')
                                    ->whereBetween('created_at', [now()->subDays(30), now()])
                                    ->groupBy('user_id')
                                    ->orderBy('comments_count', 'desc')
                                    ->take(5)
                                    ->get();

        // 最近のコメント（管理者確認用）
        $recentComments = RecipeComment::with(['user:id,name,username', 'recipe:id,title'])
                                    ->latest()
                                    ->take(10)
                                    ->get();

        return response()->json([
            'stats' => $stats,
            'top_commented_recipes' => $topCommentedRecipes,
            'active_commenters' => $activeCommenters,
            'recent_comments' => $recentComments
        ]);
    }

    /**
     * コメント検索（管理者用）
     * GET /api/admin/comments/search
     */
    public function search(Request $request)
    {
        $query = RecipeComment::with(['user:id,name,username,email', 'recipe:id,title']);

        // キーワード検索
        if ($request->has('keyword')) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('content', 'LIKE', "%{$keyword}%")
                    ->orWhereHas('user', function($userQuery) use ($keyword) {
                        $userQuery->where('name', 'LIKE', "%{$keyword}%")
                                ->orWhere('username', 'LIKE', "%{$keyword}%");
                    })
                    ->orWhereHas('recipe', function($recipeQuery) use ($keyword) {
                        $recipeQuery->where('title', 'LIKE', "%{$keyword}%");
                    });
            });
        }

        // 期間フィルター
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // ユーザーフィルター
        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // レシピフィルター
        if ($request->has('recipe_id')) {
            $query->where('recipe_id', $request->recipe_id);
        }

        $comments = $query->latest()->paginate(50);

        return response()->json($comments);
    }
}

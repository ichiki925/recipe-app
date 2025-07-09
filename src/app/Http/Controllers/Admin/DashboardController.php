<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * 管理者ダッシュボードのメイン画面
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        try {
            // 基本統計データ
            $stats = $this->getBasicStats();

            // 最近削除されたレシピ
            $deletedRecipes = $this->getRecentDeletedRecipes();

            // 最近の活動
            $recentActivities = $this->getRecentActivities();

            // 人気レシピ Top 5
            $popularRecipes = $this->getPopularRecipes();

            return response()->json([
                'data' => [
                    'stats' => $stats,
                    'deleted_recipes' => $deletedRecipes,
                    'recent_activities' => $recentActivities,
                    'popular_recipes' => $popularRecipes,
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dashboard data fetch failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'ダッシュボードデータの取得に失敗しました'
            ], 500);
        }
    }

    /**
     * 基本統計データを取得
     */
    private function getBasicStats()
    {
        // 全レシピ数（削除されたものも含む）
        $totalRecipes = Recipe::withTrashed()->count();

        // 公開中のレシピ数
        $publishedRecipes = Recipe::where('is_published', true)->count();

        // 下書きレシピ数
        $draftRecipes = Recipe::where('is_published', false)->count();

        // 最近更新されたレシピ数（7日以内）
        $recentUpdatedRecipes = Recipe::where('updated_at', '>=', Carbon::now()->subDays(7))->count();

        // ユーザー登録数
        $totalUsers = User::where('role', 'user')->count();

        // 管理者数
        $totalAdmins = User::where('role', 'admin')->count();

        // 総いいね数
        $totalLikes = RecipeLike::count();

        // 総コメント数
        $totalComments = RecipeComment::count();

        // 今日の新規ユーザー数
        $todayNewUsers = User::whereDate('created_at', Carbon::today())->count();

        // 今週のアクティビティ
        $weeklyStats = [
            'new_recipes' => Recipe::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'new_likes' => RecipeLike::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
            'new_comments' => RecipeComment::whereBetween('created_at', [
                Carbon::now()->startOfWeek(),
                Carbon::now()->endOfWeek()
            ])->count(),
        ];

        return [
            'total_recipes' => $totalRecipes,
            'published_recipes' => $publishedRecipes,
            'draft_recipes' => $draftRecipes,
            'recent_updated_recipes' => $recentUpdatedRecipes,
            'total_users' => $totalUsers,
            'total_admins' => $totalAdmins,
            'total_likes' => $totalLikes,
            'total_comments' => $totalComments,
            'today_new_users' => $todayNewUsers,
            'weekly_stats' => $weeklyStats,
        ];
    }

    /**
     * 最近削除されたレシピを取得
     */
    private function getRecentDeletedRecipes($limit = 10)
    {
        return Recipe::onlyTrashed()
            ->with('admin:id,name')
            ->latest('deleted_at')
            ->limit($limit)
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'admin_name' => $recipe->admin ? $recipe->admin->name : '不明',
                    'deleted_at' => $recipe->deleted_at->format('Y-m-d H:i'),
                    'deleted_at_human' => $recipe->deleted_at->diffForHumans(),
                ];
            });
    }

    /**
     * 最近の活動を取得
     */
    private function getRecentActivities($limit = 20)
    {
        $activities = collect();

        // 最近のレシピ投稿
        $recentRecipes = Recipe::with('admin:id,name')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($recipe) {
                return [
                    'type' => 'recipe_created',
                    'message' => "「{$recipe->title}」が投稿されました",
                    'user_name' => $recipe->admin ? $recipe->admin->name : '不明',
                    'created_at' => $recipe->created_at,
                    'url' => "/admin/recipes/{$recipe->id}",
                ];
            });

        // 最近のコメント
        $recentComments = RecipeComment::with(['user:id,name', 'recipe:id,title'])
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($comment) {
                return [
                    'type' => 'comment_created',
                    'message' => "「{$comment->recipe->title}」にコメントがありました",
                    'user_name' => $comment->user ? $comment->user->name : '不明',
                    'created_at' => $comment->created_at,
                    'url' => "/admin/comments",
                ];
            });

        // 最近のユーザー登録
        $recentUsers = User::where('role', 'user')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($user) {
                return [
                    'type' => 'user_registered',
                    'message' => "新しいユーザーが登録されました",
                    'user_name' => $user->name,
                    'created_at' => $user->created_at,
                    'url' => null,
                ];
            });

        // 全ての活動をまとめて日時順でソート
        $activities = $recentRecipes
            ->concat($recentComments)
            ->concat($recentUsers)
            ->sortByDesc('created_at')
            ->take($limit)
            ->values()
            ->map(function ($activity) {
                $activity['created_at_human'] = Carbon::parse($activity['created_at'])->diffForHumans();
                $activity['created_at_formatted'] = Carbon::parse($activity['created_at'])->format('m/d H:i');
                return $activity;
            });

        return $activities;
    }

    /**
     * 人気レシピ Top 5 を取得
     */
    private function getPopularRecipes($limit = 5)
    {
        return Recipe::published()
            ->with('admin:id,name')
            ->orderBy('likes_count', 'desc')
            ->orderBy('views_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($recipe) {
                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'likes_count' => $recipe->likes_count,
                    'views_count' => $recipe->views_count,
                    'admin_name' => $recipe->admin ? $recipe->admin->name : '不明',
                    'created_at_human' => $recipe->created_at->diffForHumans(),
                ];
            });
    }

    /**
     * システム情報を取得
     */
    public function systemInfo()
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        // データベース情報
        $dbStats = [
            'recipes_table_size' => DB::table('recipes')->count(),
            'users_table_size' => DB::table('users')->count(),
            'likes_table_size' => DB::table('recipe_likes')->count(),
            'comments_table_size' => DB::table('recipe_comments')->count(),
        ];

        // ディスク使用量（画像ファイル）
        $storageInfo = [
            'recipe_images' => $this->getDirectorySize('recipes'),
            'avatar_images' => $this->getDirectorySize('avatars'),
        ];

        return response()->json([
            'data' => [
                'database_stats' => $dbStats,
                'storage_info' => $storageInfo,
                'server_time' => now()->format('Y-m-d H:i:s'),
                'app_environment' => config('app.env'),
            ]
        ]);
    }

    /**
     * 月次レポートを取得
     */
    public function monthlyReport(Request $request)
    {
        $user = auth()->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        $month = $request->get('month', Carbon::now()->format('Y-m'));
        $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth();

        $report = [
            'period' => $month,
            'recipes_created' => Recipe::whereBetween('created_at', [$startDate, $endDate])->count(),
            'users_registered' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'likes_given' => RecipeLike::whereBetween('created_at', [$startDate, $endDate])->count(),
            'comments_posted' => RecipeComment::whereBetween('created_at', [$startDate, $endDate])->count(),
            'most_popular_genre' => $this->getMostPopularGenre($startDate, $endDate),
            'top_admin' => $this->getTopAdmin($startDate, $endDate),
        ];

        return response()->json([
            'data' => $report
        ]);
    }

    /**
     * ディレクトリサイズを取得
     */
    private function getDirectorySize($directory)
    {
        try {
            $path = storage_path("app/public/{$directory}");
            if (!is_dir($path)) {
                return 0;
            }

            $size = 0;
            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path)
            );

            foreach ($files as $file) {
                if ($file->isFile()) {
                    $size += $file->getSize();
                }
            }

            return round($size / 1024 / 1024, 2); // MB単位
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * 期間内で最も人気のジャンルを取得
     */
    private function getMostPopularGenre($startDate, $endDate)
    {
        return Recipe::whereBetween('created_at', [$startDate, $endDate])
            ->select('genre', DB::raw('COUNT(*) as count'))
            ->groupBy('genre')
            ->orderBy('count', 'desc')
            ->first();
    }

    /**
     * 期間内で最も活発な管理者を取得
     */
    private function getTopAdmin($startDate, $endDate)
    {
        return Recipe::with('admin:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('admin_id', DB::raw('COUNT(*) as recipes_count'))
            ->groupBy('admin_id')
            ->orderBy('recipes_count', 'desc')
            ->first();
    }
}
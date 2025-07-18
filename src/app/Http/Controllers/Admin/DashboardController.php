<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\User;
use App\Models\RecipeLike;
use App\Models\RecipeComment;
use App\Http\Resources\AdminRecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class DashboardController extends Controller
{
    /**
     * 管理者ダッシュボードのメイン画面
     */
    public function index(Request $request)
    {
        $user = $request->user();

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

            \Log::info('Dashboard data prepared successfully', [
                'user_id' => $user->id,
                'stats' => $stats
            ]);

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
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return response()->json([
                'message' => 'ダッシュボードデータの取得に失敗しました',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 基本統計データを取得
     */
    private function getBasicStats()
    {
        try {
            // ユーザー統計（これは確実に存在）
            $totalUsers = User::where('role', 'user')->count();
            $totalAdmins = User::where('role', 'admin')->count();
            $todayNewUsers = User::whereDate('created_at', Carbon::today())->count();

            // レシピ統計（テーブルが存在するかチェック）
            $recipeStats = [
                'total_recipes' => 0,
                'published_recipes' => 0,
                'draft_recipes' => 0,
                'recent_updated_recipes' => 0,
            ];

            if (Schema::hasTable('recipes')) {
                try {
                    $recipeStats['total_recipes'] = Recipe::withTrashed()->count();
                    $recipeStats['published_recipes'] = Recipe::where('is_published', true)->count();
                    $recipeStats['draft_recipes'] = Recipe::where('is_published', false)->count();
                    $recipeStats['recent_updated_recipes'] = Recipe::where('updated_at', '>=', Carbon::now()->subDays(7))->count();
                } catch (\Exception $e) {
                    \Log::warning('Recipe stats error: ' . $e->getMessage());
                }
            }

            // いいね統計
            $totalLikes = 0;
            if (Schema::hasTable('recipe_likes')) {
                try {
                    $totalLikes = RecipeLike::count();
                } catch (\Exception $e) {
                    \Log::warning('Likes stats error: ' . $e->getMessage());
                }
            }

            // コメント統計
            $totalComments = 0;
            if (Schema::hasTable('recipe_comments')) {
                try {
                    $totalComments = RecipeComment::count();
                } catch (\Exception $e) {
                    \Log::warning('Comments stats error: ' . $e->getMessage());
                }
            }

            // 今週のアクティビティ統計
            $weeklyStats = [
                'new_recipes' => 0,
                'new_likes' => 0,
                'new_comments' => 0,
            ];

            if (Schema::hasTable('recipes')) {
                try {
                    $weeklyStats['new_recipes'] = Recipe::whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])->count();
                } catch (\Exception $e) {
                    \Log::warning('Weekly recipes error: ' . $e->getMessage());
                }
            }

            if (Schema::hasTable('recipe_likes')) {
                try {
                    $weeklyStats['new_likes'] = RecipeLike::whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])->count();
                } catch (\Exception $e) {
                    \Log::warning('Weekly likes error: ' . $e->getMessage());
                }
            }

            if (Schema::hasTable('recipe_comments')) {
                try {
                    $weeklyStats['new_comments'] = RecipeComment::whereBetween('created_at', [
                        Carbon::now()->startOfWeek(),
                        Carbon::now()->endOfWeek()
                    ])->count();
                } catch (\Exception $e) {
                    \Log::warning('Weekly comments error: ' . $e->getMessage());
                }
            }

            return array_merge($recipeStats, [
                'total_users' => $totalUsers,
                'total_admins' => $totalAdmins,
                'total_likes' => $totalLikes,
                'total_comments' => $totalComments,
                'today_new_users' => $todayNewUsers,
                'weekly_stats' => $weeklyStats,
            ]);

        } catch (\Exception $e) {
            \Log::error('getBasicStats error: ' . $e->getMessage());

            // エラーが発生した場合は最小限の統計を返す
            return [
                'total_recipes' => 0,
                'published_recipes' => 0,
                'draft_recipes' => 0,
                'recent_updated_recipes' => 0,
                'total_users' => User::count(),
                'total_admins' => User::where('role', 'admin')->count(),
                'total_likes' => 0,
                'total_comments' => 0,
                'today_new_users' => 0,
                'weekly_stats' => [
                    'new_recipes' => 0,
                    'new_likes' => 0,
                    'new_comments' => 0,
                ],
            ];
        }
    }

    /**
     * 最近削除されたレシピを取得
     */
    private function getRecentDeletedRecipes($limit = 10)
    {
        try {
            if (!Schema::hasTable('recipes')) {
                return [];
            }

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
        } catch (\Exception $e) {
            \Log::warning('getRecentDeletedRecipes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 最近の活動を取得
     */
    private function getRecentActivities($limit = 20)
    {
        try {
            $activities = collect();

            // 最近のユーザー登録（これは確実に取得可能）
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

            $activities = $activities->concat($recentUsers);

            // レシピ関連の活動（テーブルが存在する場合のみ）
            if (Schema::hasTable('recipes')) {
                try {
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

                    $activities = $activities->concat($recentRecipes);
                } catch (\Exception $e) {
                    \Log::warning('Recent recipes error: ' . $e->getMessage());
                }
            }

            // コメント関連の活動
            if (Schema::hasTable('recipe_comments')) {
                try {
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

                    $activities = $activities->concat($recentComments);
                } catch (\Exception $e) {
                    \Log::warning('Recent comments error: ' . $e->getMessage());
                }
            }

            // 全ての活動をまとめて日時順でソート
            return $activities
                ->sortByDesc('created_at')
                ->take($limit)
                ->values()
                ->map(function ($activity) {
                    $activity['created_at_human'] = Carbon::parse($activity['created_at'])->diffForHumans();
                    $activity['created_at_formatted'] = Carbon::parse($activity['created_at'])->format('m/d H:i');
                    return $activity;
                });

        } catch (\Exception $e) {
            \Log::warning('getRecentActivities error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 人気レシピ Top 5 を取得
     */
    private function getPopularRecipes($limit = 5)
    {
        try {
            if (!Schema::hasTable('recipes')) {
                return [];
            }

            $recipes = Recipe::published()
                ->with(['admin', 'comments', 'likes'])
                ->orderBy('likes_count', 'desc')
                ->orderBy('views_count', 'desc')
                ->limit($limit)
                ->get();

            return AdminRecipeResource::collection($recipes);

        } catch (\Exception $e) {
            \Log::warning('getPopularRecipes error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * システム情報を取得
     */
    public function systemInfo(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->isAdmin()) {
            return response()->json([
                'message' => '管理者権限が必要です'
            ], 403);
        }

        // データベース情報
        $dbStats = [
            'recipes_table_size' => Schema::hasTable('recipes') ? DB::table('recipes')->count() : 0,
            'users_table_size' => DB::table('users')->count(),
            'likes_table_size' => Schema::hasTable('recipe_likes') ? DB::table('recipe_likes')->count() : 0,
            'comments_table_size' => Schema::hasTable('recipe_comments') ? DB::table('recipe_comments')->count() : 0,
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
        $user = $request->user();

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
            'recipes_created' => Schema::hasTable('recipes') ? Recipe::whereBetween('created_at', [$startDate, $endDate])->count() : 0,
            'users_registered' => User::whereBetween('created_at', [$startDate, $endDate])->count(),
            'likes_given' => Schema::hasTable('recipe_likes') ? RecipeLike::whereBetween('created_at', [$startDate, $endDate])->count() : 0,
            'comments_posted' => Schema::hasTable('recipe_comments') ? RecipeComment::whereBetween('created_at', [$startDate, $endDate])->count() : 0,
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
        try {
            if (!Schema::hasTable('recipes')) {
                return null;
            }

            return Recipe::whereBetween('created_at', [$startDate, $endDate])
                ->select('genre', DB::raw('COUNT(*) as count'))
                ->groupBy('genre')
                ->orderBy('count', 'desc')
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }


    /**
     * 期間内で最も活発な管理者を取得
     */
    private function getTopAdmin($startDate, $endDate)
    {
        try {
            if (!Schema::hasTable('recipes')) {
                return null;
            }

            return Recipe::with('admin:id,name')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('admin_id', DB::raw('COUNT(*) as recipes_count'))
                ->groupBy('admin_id')
                ->orderBy('recipes_count', 'desc')
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}
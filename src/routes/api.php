<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;



// ========================================
// 🧪 テスト用エンドポイント（既存保持）
// ========================================

// 認証不要のテスト用エンドポイント
Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

// Firebase認証が必要なテスト用エンドポイント
Route::middleware('firebase.auth')->group(function () {

    // 認証済みユーザー情報を取得
    Route::get('/auth/user', function (Request $request) {
        $user = $request->user();

        return response()->json([
            'message' => 'Firebase authentication successful!',
            'user' => [
                'id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'name' => $user->name,
                'email' => $user->email,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'is_admin' => $user->isAdmin(),
                'is_user' => $user->isUser(),
                'created_at' => $user->created_at,
            ]
        ]);
    });

    // 管理者専用エンドポイント（テスト用）
    Route::get('/auth/admin-only', function (Request $request) {
        $user = $request->user();

        if (!$user->isAdmin()) {
            return response()->json([
                'error' => 'Admin access required'
            ], 403);
        }

        return response()->json([
            'message' => 'Admin access granted!',
            'admin' => $user->name,
        ]);
    });

    // ユーザー専用エンドポイント（テスト用）
    Route::get('/auth/user-only', function (Request $request) {
        $user = $request->user();

        if (!$user->isUser()) {
            return response()->json([
                'error' => 'User access required'
            ], 403);
        }

        return response()->json([
            'message' => 'User access granted!',
            'user' => $user->name,
        ]);
    });

    // データベース接続テスト
    Route::get('/auth/db-test', function (Request $request) {
        $user = $request->user();
        $userCount = \App\Models\User::count();

        return response()->json([
            'message' => 'Database connection successful!',
            'current_user' => $user->name,
            'total_users' => $userCount,
        ]);
    });
});

// ========================================
// 🌐 公開API（未ログインユーザー）
// ========================================

Route::prefix('recipes')->group(function () {
    // レシピ一覧（基本情報のみ）
    Route::get('/', [RecipeController::class, 'index']);

    // レシピ検索
    Route::get('/search', [RecipeController::class, 'search']);
});

// ========================================
// 🔐 認証必須API（ログインユーザー）
// ========================================

Route::middleware('firebase.auth')->group(function () {

    // レシピ詳細（認証必須）
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);

    // いいね機能
    Route::prefix('recipes/{recipe}')->group(function () {
        Route::post('/like', [LikeController::class, 'store']);
        Route::delete('/like', [LikeController::class, 'destroy']);
        Route::post('/toggle-like', [LikeController::class, 'toggle']);
        Route::get('/likes', [LikeController::class, 'index']);
    });

    // コメント機能
    Route::prefix('recipes/{recipe}')->group(function () {
        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
    });

    // ユーザー機能
    Route::prefix('user')->group(function () {
        // プロフィール
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // お気に入り
        Route::get('/liked-recipes', [LikeController::class, 'userLikes']);
    });
});

// ========================================
// 🔑 管理者専用API
// ========================================

Route::middleware(['firebase.auth', 'admin'])->prefix('admin')->group(function () {

    // ダッシュボード
    Route::get('/dashboard', [DashboardController::class, 'index']);
    // Route::get('/dashboard/system-info', [DashboardController::class, 'systemInfo']);
    // Route::get('/dashboard/monthly-report', [DashboardController::class, 'monthlyReport']);

    // レシピ管理（CRUD）
    Route::prefix('recipes')->group(function () {
        Route::get('/', [RecipeController::class, 'adminIndex']);
        Route::post('/', [RecipeController::class, 'store']);
        Route::get('/{recipe}', [RecipeController::class, 'adminShow']);
        Route::put('/{recipe}', [RecipeController::class, 'update']);
        Route::delete('/{recipe}', [RecipeController::class, 'destroy']);
    });

    // コメント管理
    Route::prefix('comments')->group(function () {
        Route::get('/', [Admin\CommentController::class, 'index']);
        Route::get('/stats', [Admin\CommentController::class, 'stats']);
        Route::get('/flagged', [Admin\CommentController::class, 'flagged']);
        Route::get('/user/{user}', [Admin\CommentController::class, 'userComments']);
        Route::get('/{comment}', [Admin\CommentController::class, 'show']);
        Route::delete('/{comment}', [Admin\CommentController::class, 'destroy']);
        Route::delete('/bulk', [Admin\CommentController::class, 'bulkDestroy']);
    });

    // いいね統計（追加）
    Route::get('/like-stats', [LikeController::class, 'stats']);
    Route::get('/comment-stats', [CommentController::class, 'stats']);
});
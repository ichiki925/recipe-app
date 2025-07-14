<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Resources\UserResource;



// ========================================
// 🔥 認証関連
// ========================================

// 一般ユーザー認証ルート
Route::prefix('auth')->group(function () {
    // 登録（認証不要）
    Route::post('/register', [AuthController::class, 'register']);

    // 認証が必要なルート
    Route::middleware('firebase.auth')->group(function () {
        Route::get('/check', [AuthController::class, 'check']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// 管理者認証ルート
Route::prefix('admin')->group(function () {
    // 管理者登録（認証不要）
    Route::post('/register', [AdminAuthController::class, 'register']);

    // 管理者認証が必要なルート
    Route::middleware(['firebase.auth', 'admin'])->group(function () {
        Route::get('/check', [AdminAuthController::class, 'check']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});

// その他のAPIルート
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});


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

    Route::get('/auth/user', function (Request $request) {
        return response()->json([
            'message' => 'Firebase authentication successful!',
            'user' => new UserResource($request->user()),
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
        $userCount = User::count();

        return response()->json([
            'message' => 'Database connection successful!',
            'current_user' => $user->name,
            'total_users' => $userCount,
        ]);
    });
});

// ========================================
// 🧪 開発用テストルート（本番時削除）
// ========================================
Route::get('/test/profile', function() {
    $user = User::first();
    auth()->setUser($user);
    
    $controller = new App\Http\Controllers\User\ProfileController();
    return $controller->show(request());
});

Route::get('/test/user-resource', function() {
    $user = User::first();
    return response()->json([
        'message' => 'UserResource test',
        'user' => new App\Http\Resources\UserResource($user),
    ]);
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
        Route::get('/', [AdminCommentController::class, 'index']);
        Route::get('/stats', [AdminCommentController::class, 'stats']);
        Route::get('/flagged', [AdminCommentController::class, 'flagged']);
        Route::get('/user/{user}', [AdminCommentController::class, 'userComments']);
        Route::get('/{comment}', [AdminCommentController::class, 'show']);
        Route::delete('/{comment}', [AdminCommentController::class, 'destroy']);
        Route::delete('/bulk', [AdminCommentController::class, 'bulkDestroy']);
    });

    // いいね統計
    Route::get('/like-stats', [LikeController::class, 'stats']);
    Route::get('/comment-stats', [CommentController::class, 'stats']);
});
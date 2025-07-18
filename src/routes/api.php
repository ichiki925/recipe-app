<?php

// routes/api.php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Recipe;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\LikeController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Resources\UserResource;
use App\Http\Resources\RecipeResource;
use App\Http\Controllers\Admin\UserController;



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

Route::get('/test/recipe', function() {
    $recipe = Recipe::with(['admin', 'comments.user'])->first();
    return response()->json([
        'message' => 'RecipeResource test',
        'recipe' => new App\Http\Resources\RecipeResource($recipe),
    ]);
});

Route::get('/test/recipe-collection', function() {
    $recipes = Recipe::with('admin')->published()->paginate(5);
    return new App\Http\Resources\RecipeCollection($recipes);
});

Route::get('/test/recipe-controller/{recipe}', [App\Http\Controllers\Api\RecipeController::class, 'show']);

// CommentResource テスト用
Route::get('/test/comment-resource', function() {
    $comment = \App\Models\RecipeComment::with(['user', 'recipe'])->first();
    return response()->json([
        'message' => 'CommentResource test',
        'comment' => new \App\Http\Resources\CommentResource($comment),
    ]);
});

// RecipeLikeResource テスト用
Route::get('/test/like-resource', function() {
    $like = \App\Models\RecipeLike::with(['user', 'recipe'])->first();
    return response()->json([
        'message' => 'RecipeLikeResource test',
        'like' => new \App\Http\Resources\RecipeLikeResource($like),
    ]);
});

// コメント一覧テスト用
Route::get('/test/comments/{recipe}', function($recipeId) {
    $recipe = \App\Models\Recipe::findOrFail($recipeId);
    $comments = $recipe->comments()->with('user')->take(3)->get();
    return response()->json([
        'message' => 'Comments collection test',
        'data' => \App\Http\Resources\CommentResource::collection($comments),
    ]);
});

// いいね一覧テスト用
Route::get('/test/likes/{recipe}', function($recipeId) {
    $recipe = \App\Models\Recipe::findOrFail($recipeId);
    $likes = $recipe->likes()->with(['user', 'recipe'])->take(3)->get();
    return response()->json([
        'message' => 'Likes collection test',
        'data' => \App\Http\Resources\RecipeLikeResource::collection($likes),
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

    // いいね機能（Resource対応 + 便利メソッド）
    Route::prefix('recipes/{recipe}')->group(function () {
        // Standard RESTful routes
        Route::get('/likes', [LikeController::class, 'index']);           // いいね一覧
        Route::post('/likes', [LikeController::class, 'store']);          // いいね作成
        Route::delete('/likes', [LikeController::class, 'destroyByRecipe']); // レシピのいいね削除

        // 便利メソッド
        Route::post('/toggle-like', [LikeController::class, 'toggle']);   // いいねトグル
    });

    // 個別いいねリソース
    Route::get('/likes/{like}', [LikeController::class, 'show']);         // いいね詳細
    Route::delete('/likes/{like}', [LikeController::class, 'destroy']);   // 個別いいね削除

    // コメント機能（Resource対応）
    Route::prefix('recipes/{recipe}')->group(function () {
        Route::get('/comments', [CommentController::class, 'index']);     // コメント一覧
        Route::post('/comments', [CommentController::class, 'store']);    // コメント作成
    });

    // 個別コメントリソース
    Route::get('/comments/{comment}', [CommentController::class, 'show']);       // コメント詳細
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']); // コメント削除


    // ユーザー機能
    Route::prefix('user')->group(function () {
        // プロフィール
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);

        // ユーザーの投稿履歴
        Route::get('/liked-recipes', [LikeController::class, 'userLikes']);      // お気に入りレシピ
        Route::get('/comments', [CommentController::class, 'userComments']);     // 投稿したコメント
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

    // ユーザー統計のみ（ユーザー一覧や詳細は不要）
    Route::get('/users/stats', [UserController::class, 'stats']);
});
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

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::middleware('firebase.auth')->group(function () {
        Route::get('/check', [AuthController::class, 'check']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

Route::prefix('admin')->group(function () {
    Route::post('/register', [AdminAuthController::class, 'register']);
    Route::middleware(['firebase.auth', 'admin'])->group(function () {
        Route::get('/check', [AdminAuthController::class, 'check']);
        Route::post('/logout', [AdminAuthController::class, 'logout']);
    });
});
// その他のAPIルート
// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


// ========================================
// 🧪 テスト用エンドポイント（既存保持）
// ========================================

Route::get('/test', function () {
    return response()->json([
        'message' => 'API is working!',
        'timestamp' => now(),
    ]);
});

Route::middleware('firebase.auth')->group(function () {
    Route::get('/auth/user', function (Request $request) {
        return response()->json([
            'message' => 'Firebase authentication successful!',
            'user' => new UserResource($request->user()),
        ]);
    });

    Route::get('/auth/admin-only', function (Request $request) {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json(['error' => 'Admin access required'], 403);
        }
        return response()->json([
            'message' => 'Admin access granted!',
            'admin' => $user->name,
        ]);
    });

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

// // ========================================
// // 🧪 開発用テストルート（本番時削除）
// // ========================================
// Route::get('/test/profile', function() {
//     $user = User::first();
//     auth()->setUser($user);
    
//     $controller = new App\Http\Controllers\User\ProfileController();
//     return $controller->show(request());
// });

// Route::get('/test/user-resource', function() {
//     $user = User::first();
//     return response()->json([
//         'message' => 'UserResource test',
//         'user' => new App\Http\Resources\UserResource($user),
//     ]);
// });

// Route::get('/test/recipe', function() {
//     $recipe = Recipe::with(['admin', 'comments.user'])->first();
//     return response()->json([
//         'message' => 'RecipeResource test',
//         'recipe' => new App\Http\Resources\RecipeResource($recipe),
//     ]);
// });

// Route::get('/test/recipe-collection', function() {
//     $recipes = Recipe::with('admin')->published()->paginate(5);
//     return new App\Http\Resources\RecipeCollection($recipes);
// });

// Route::get('/test/recipe-controller/{recipe}', [App\Http\Controllers\Api\RecipeController::class, 'show']);

// // CommentResource テスト用
// Route::get('/test/comment-resource', function() {
//     $comment = \App\Models\RecipeComment::with(['user', 'recipe'])->first();
//     return response()->json([
//         'message' => 'CommentResource test',
//         'comment' => new \App\Http\Resources\CommentResource($comment),
//     ]);
// });

// // RecipeLikeResource テスト用
// Route::get('/test/like-resource', function() {
//     $like = \App\Models\RecipeLike::with(['user', 'recipe'])->first();
//     return response()->json([
//         'message' => 'RecipeLikeResource test',
//         'like' => new \App\Http\Resources\RecipeLikeResource($like),
//     ]);
// });

// // コメント一覧テスト用
// Route::get('/test/comments/{recipe}', function($recipeId) {
//     $recipe = \App\Models\Recipe::findOrFail($recipeId);
//     $comments = $recipe->comments()->with('user')->take(3)->get();
//     return response()->json([
//         'message' => 'Comments collection test',
//         'data' => \App\Http\Resources\CommentResource::collection($comments),
//     ]);
// });

// // いいね一覧テスト用
// Route::get('/test/likes/{recipe}', function($recipeId) {
//     $recipe = \App\Models\Recipe::findOrFail($recipeId);
//     $likes = $recipe->likes()->with(['user', 'recipe'])->take(3)->get();
//     return response()->json([
//         'message' => 'Likes collection test',
//         'data' => \App\Http\Resources\RecipeLikeResource::collection($likes),
//     ]);
// });

// ==============================================
// 🧪 デバッグ用テストルート（開発環境のみ）
// ==============================================

if (config('app.env') === 'local') {
    // 1. 認証なしでレシピ取得をテスト
    Route::get('/debug/recipe/{id}', function($id) {
        try {
            $recipe = \App\Models\Recipe::withTrashed()
                ->with(['admin', 'comments.user', 'likes.user'])
                ->findOrFail($id);
                
            return response()->json([
                'status' => 'success',
                'message' => 'デバッグ: レシピ取得成功',
                'data' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'admin_name' => $recipe->admin->name ?? 'N/A',
                    'comments_count' => $recipe->comments->count(),
                    'likes_count' => $recipe->likes->count(),
                    'ingredients_length' => strlen($recipe->ingredients ?? ''),
                    'instructions_length' => strlen($recipe->instructions ?? ''),
                    'image_url' => $recipe->image_url,
                    'is_published' => $recipe->is_published,
                    'created_at' => $recipe->created_at->toISOString(),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    });

    // 2. 認証状態をテスト
    Route::middleware('firebase.auth')->get('/debug/auth', function(Request $request) {
        $user = $request->user();
        return response()->json([
            'status' => 'success',
            'message' => 'デバッグ: 認証成功',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_admin' => $user->isAdmin()
            ]
        ]);
    });

    // 3. 管理者権限をテスト
    Route::middleware(['firebase.auth', 'admin'])->get('/debug/admin', function(Request $request) {
        return response()->json([
            'status' => 'success',
            'message' => 'デバッグ: 管理者権限確認成功',
            'user' => $request->user()->name
        ]);
    });

    // 4. 完全なルートテスト
    Route::middleware(['firebase.auth', 'admin'])->get('/debug/admin-recipe/{id}', function($id, Request $request) {
        \Log::info('Debug admin recipe route', ['id' => $id]);
        
        try {
            $recipe = \App\Models\Recipe::withTrashed()
                ->with(['admin', 'comments.user', 'likes.user'])
                ->findOrFail($id);
                
            $resource = new \App\Http\Resources\AdminRecipeResource($recipe);
            
            return response()->json([
                'status' => 'success',
                'message' => 'デバッグ: 完全なルートテスト成功',
                'data' => $resource
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    });
}

// テスト用のシンプルなエンドポイント
Route::get('/test-simple', function() {
    return response()->json([
        'status' => 'success',
        'message' => 'API is working',
        'timestamp' => now()
    ]);
});

// ========================================
// 🌐 公開API（未ログインユーザー）
// ========================================

Route::prefix('recipes')->group(function () {
    Route::get('/', [RecipeController::class, 'index']);
    Route::get('/search', [RecipeController::class, 'search']);
});
// ========================================
// 🔐 認証必須API（ログインユーザー）
// ========================================

Route::middleware('firebase.auth')->group(function () {
    Route::get('/recipes/{recipe}', [RecipeController::class, 'show']);

    Route::prefix('recipes/{recipe}')->group(function () {
        Route::get('/likes', [LikeController::class, 'index']);
        Route::post('/likes', [LikeController::class, 'store']);
        Route::delete('/likes', [LikeController::class, 'destroyByRecipe']);
        Route::post('/toggle-like', [LikeController::class, 'toggle']);
    });

    Route::get('/likes/{like}', [LikeController::class, 'show']);
    Route::delete('/likes/{like}', [LikeController::class, 'destroy']);

    Route::prefix('recipes/{recipe}')->group(function () {
        Route::get('/comments', [CommentController::class, 'index']);
        Route::post('/comments', [CommentController::class, 'store']);
    });

    Route::get('/comments/{comment}', [CommentController::class, 'show']);
    Route::delete('/comments/{comment}', [CommentController::class, 'destroy']);

    Route::prefix('user')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show']);
        Route::put('/profile', [ProfileController::class, 'update']);
        Route::get('/liked-recipes', [LikeController::class, 'userLikes']);
        Route::get('/comments', [CommentController::class, 'userComments']);
    });
});


// ========================================
// 🔑 管理者専用API
// ========================================

Route::middleware(['firebase.auth', 'admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/recipes', [RecipeController::class, 'adminIndex']);
    
    // レシピ作成
    Route::post('/recipes', [RecipeController::class, 'store']);
    
    // レシピ詳細（デバッグログ付き）
    Route::get('/recipes/{id}', function($id, Request $request) {
        \Log::info('=== Admin Recipe Show Route Called ===', [
            'recipe_id' => $id,
            'request_method' => $request->method(),
            'request_headers' => $request->headers->all(),
            'user_authenticated' => !!$request->user(),
            'user_id' => $request->user() ? $request->user()->id : null,
            'timestamp' => now()
        ]);
        
        $controller = new \App\Http\Controllers\Api\RecipeController();
        return $controller->adminShow($id);
    })->where('id', '[0-9]+'); // IDは数値のみ許可
    Route::put('/recipes/{recipe}', [RecipeController::class, 'update']);
    Route::delete('/recipes/{recipe}', [RecipeController::class, 'destroy']);

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

    Route::get('/like-stats', [LikeController::class, 'stats']);
    Route::get('/users/stats', [UserController::class, 'stats']);
});

// ========================================
// 🧪 テスト用ルート（最後に配置）
// ========================================

// 認証なしテスト
Route::post('/test-recipe-simple', function(Request $request) {
    \Log::info('Simple test endpoint called', [
        'title' => $request->get('title'),
        'method' => $request->method(),
    ]);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Test endpoint working!',
        'data' => [
            'id' => 999,
            'title' => $request->get('title')
        ]
    ]);
});

// 認証ありテスト
Route::middleware(['firebase.auth', 'admin'])->post('/test-recipe-auth', function(Request $request) {
    $user = $request->user();
    \Log::info('Auth test endpoint called', [
        'user_id' => $user->id,
        'user_role' => $user->role,
        'title' => $request->get('title')
    ]);
    
    return response()->json([
        'status' => 'success',
        'message' => 'Auth test endpoint working!',
        'user' => $user->name,
        'data' => [
            'id' => 888,
            'title' => $request->get('title')
        ]
    ]);
});
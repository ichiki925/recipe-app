<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminLoginController;
use App\Http\Controllers\User\RecipeController as UserRecipeController;
use App\Models\Recipe;
use App\Http\Controllers\User\MyPageController;



Route::get('/', function () {
    return view('welcome');
});


// ユーザー用
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');

// 管理者ログイン関連（表示と処理）
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminLoginController::class, 'showLoginForm'])->name('login'); // ログインフォーム
    Route::post('login', [AdminLoginController::class, 'login']); // ログイン処理
});

// 管理者認証が必要なルート群
Route::prefix('admin')->name('admin.')->group(function () {
    
    Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
    Route::post('/recipes', [RecipeController::class, 'store'])->name('admin.recipes.store');
    Route::get('/recipes/create', [RecipeController::class, 'create'])->name('admin.recipes.create');

    
    // ダッシュボード
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // レシピ関連
    
    // 必要に応じて：
    // Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
    // Route::get('recipes/{id}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    // Route::put('recipes/{id}', [RecipeController::class, 'update'])->name('recipes.update');
    // Route::delete('recipes/{id}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
});

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('comments', function () {
        $comments = collect([
            (object)[
                'id' => 1,
                'user' => (object)['name' => 'テストユーザー'],
                'recipe' => (object)['title' => 'ハンバーグ'],
                'body' => 'とてもおいしかったです！',
                'created_at' => now(),
            ],
            (object)[
                'id' => 2,
                'user' => (object)['name' => 'サンプル太郎'],
                'recipe' => (object)['title' => 'カレーライス'],
                'body' => 'また作ります！',
                'created_at' => now(),
            ],
        ]);
        return view('admin.comments.index', compact('comments'));
    })->name('comments.index');
});

Route::get('/recipes', [UserRecipeController::class, 'index'])->name('user.recipes.index');
Route::get('/recipes/{id}', [UserRecipeController::class, 'show'])->name('user.recipes.show');

Route::middleware(['auth'])->group(function () {
    Route::get('/mypage', [MyPageController::class, 'mypage'])->name('mypage');
});

Route::get('/profile', function () {
    // ログインユーザーを仮で取得（開発時のみ）
    $user = Auth::user() ?? (object)[
        'name' => 'サンプルユーザー',
        'email' => 'sample@example.com',
        'avatar' => null, // または 'avatars/sample.jpg'
    ];
    return view('user.profile', compact('user'));
});
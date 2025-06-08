<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminLoginController;



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
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
        Route::post('/recipes', [RecipeController::class, 'store'])->name('admin.recipes.store');
    });
    // ダッシュボード
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // レシピ関連
    
    Route::get('recipes/create', [RecipeController::class, 'create'])->name('recipes.create');

    // 必要に応じて：
    // Route::post('recipes', [RecipeController::class, 'store'])->name('recipes.store');
    // Route::get('recipes/{id}/edit', [RecipeController::class, 'edit'])->name('recipes.edit');
    // Route::put('recipes/{id}', [RecipeController::class, 'update'])->name('recipes.update');
    // Route::delete('recipes/{id}', [RecipeController::class, 'destroy'])->name('recipes.destroy');
});
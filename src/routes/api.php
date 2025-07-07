<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

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
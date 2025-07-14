<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{

    public function register(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'firebase_uid' => 'required|string|unique:users,firebase_uid',
                'name' => 'required|string|max:20|min:2',
                'email' => 'required|email|unique:users,email',
            ]);

            // 新規ユーザー作成
            $user = User::create([
                'firebase_uid' => $validated['firebase_uid'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => 'user',
                'email_verified_at' => now(),
            ]);

            // 成功ログ
            Log::info('User created successfully', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'email' => $user->email,
                'created_by' => 'registration_form'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ユーザー登録が完了しました',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'firebase_uid' => $user->firebase_uid,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'created_at' => $user->created_at,
                    ]
                ]
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('User registration validation failed', [
                'errors' => $e->errors(),
                'input' => $request->only(['name', 'email', 'firebase_uid'])
            ]);

            return response()->json([
                'success' => false,
                'error' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('User registration failed', [
                'error' => $e->getMessage(),
                'firebase_uid' => $request->firebase_uid ?? null,
                'email' => $request->email ?? null,
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'ユーザー登録に失敗しました',
                'message' => 'サーバーエラーが発生しました'
            ], 500);
        }
    }

    /**
     * ユーザー情報確認
     */
    public function check(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('User access granted', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'role' => $user->role
            ]);

            return response()->json([
                'success' => true,  // 🔧 統一性のため追加
                'user' => [
                    'id' => $user->id,
                    'firebase_uid' => $user->firebase_uid,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'avatar' => $user->avatar ?? null,
                    'created_at' => $user->created_at,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('User check failed', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'ユーザー情報の取得に失敗しました'
            ], 500);
        }
    }

    /**
     * ログアウト処理
     */
    public function logout(Request $request)
    {
        try {
            $user = $request->user();

            Log::info('User logged out', [
                'user_id' => $user->id,
                'firebase_uid' => $user->firebase_uid
            ]);

            return response()->json([
                'success' => true,
                'message' => 'ログアウトしました'
            ]);

        } catch (\Exception $e) {
            Log::error('Logout failed', [
                'error' => $e->getMessage(),
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'ログアウトに失敗しました'
            ], 500);
        }
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * 新規管理者登録
     */
    public function register(Request $request)
    {
        try {
            // バリデーション
            $validated = $request->validate([
                'admin_code' => 'required|string',
                'firebase_uid' => 'required|string|unique:users,firebase_uid',
                'name' => 'required|string|max:20|min:2',
                'email' => 'required|email|unique:users,email',
            ]);

            // 管理者コード確認
            if ($validated['admin_code'] !== 'VANILLA_KITCHEN_ADMIN_2025') {
                return response()->json([
                    'error' => '無効な管理者コードです'
                ], 422);
            }

            // 新規管理者ユーザー作成
            $admin = User::create([
                'firebase_uid' => $validated['firebase_uid'],
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);

            // 成功ログ
            Log::info('Admin user created successfully', [
                'admin_id' => $admin->id,
                'firebase_uid' => $admin->firebase_uid,
                'email' => $admin->email,
                'created_by' => 'registration_form'
            ]);

            return response()->json([
                'success' => true,
                'message' => '管理者登録が完了しました',
                'admin' => [
                    'id' => $admin->id,
                    'firebase_uid' => $admin->firebase_uid,
                    'name' => $admin->name,
                    'email' => $admin->email,
                    'role' => $admin->role,
                    'created_at' => $admin->created_at,
                ]
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('Admin registration validation failed', [
                'errors' => $e->errors(),
                'input' => $request->only(['name', 'email', 'firebase_uid'])
            ]);

            return response()->json([
                'error' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Admin registration failed', [
                'error' => $e->getMessage(),
                'firebase_uid' => $request->firebase_uid ?? null,
                'email' => $request->email ?? null,
                'stack' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => '管理者登録に失敗しました'
            ], 500);
        }
    }

    /**
     * 管理者権限確認
     */
    public function check(Request $request)
    {
        $user = $request->user();

        if (!$user->isAdmin()) {
            Log::warning('Non-admin user tried to access admin endpoint', [
                'user_id' => $user->id,
                'user_role' => $user->role,
                'firebase_uid' => $user->firebase_uid
            ]);

            return response()->json([
                'error' => 'Admin access required'
            ], 403);
        }

        Log::info('Admin access granted', [
            'admin_id' => $user->id,
            'firebase_uid' => $user->firebase_uid
        ]);

        return response()->json([
            'admin' => $user
        ]);
    }
}
<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserProfileResource;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Response;


class ProfileController extends Controller
{
    /**
     * ユーザープロフィール表示
     */
    public function show(Request $request)
    {
        \Log::info('=== ProfileController show method called ===', [
        'method' => $request->method(),
        'url' => $request->fullUrl(),
        'headers' => $request->headers->all(),
        'auth_check' => auth()->check(),
        'auth_user' => auth()->user() ? auth()->user()->toArray() : null,
        'has_bearer_token' => !empty($request->bearerToken())
    ]);

        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        // 統計情報のためにリレーションを読み込み
        $user->loadCount(['recipeLikes', 'recipeComments', 'likedRecipes', 'recipes']);

        return response()->json([
            'data' => new UserProfileResource($user)
        ]);
    }

    /**
     * ユーザープロフィール更新
     */
    public function update(ProfileUpdateRequest $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        $validatedData = $request->validated();

        \Log::info('✅ バリデーション通過:', [
            'validated_data' => $validatedData,
            'name_character_count' => isset($validatedData['name']) ? mb_strlen($validatedData['name'], 'UTF-8') : 'N/A',
        ]);

        // 🔧 アバター画像のアップロード処理
        $avatarUrl = $user->avatar_url;
        if ($request->hasFile('avatar')) {
            try {
                \Log::info('📷 アバター画像アップロード開始');
                
                // 古いアバター画像を削除
                if ($user->avatar_url && $user->avatar_url !== '/images/default-avatar.png') {
                    $oldImagePath = str_replace('/storage/', '', $user->avatar_url);
                    if (Storage::disk('public')->exists($oldImagePath)) {
                        Storage::disk('public')->delete($oldImagePath);
                        \Log::info('🗑️ 古いアバター画像を削除:', ['path' => $oldImagePath]);
                    }
                }

                // 新しいアバター画像をアップロード
                $avatarUrl = $this->handleAvatarUpload($request->file('avatar'));
                \Log::info('📷 新しいアバター画像をアップロード:', ['url' => $avatarUrl]);
                
            } catch (\Exception $e) {
                \Log::error('❌ アバター画像アップロードエラー:', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                
                return response()->json([
                    'message' => 'アバター画像のアップロードに失敗しました',
                    'errors' => [
                        'avatar' => ['画像のアップロードに失敗しました: ' . $e->getMessage()]
                    ]
                ], 422);
            }
        }


        // ✅ ユーザー情報を更新
        $updateData = [];

        if ($request->has('name')) {
            $updateData['name'] = trim($request->name);
            \Log::info('🔧 名前を更新:', ['new_name' => $updateData['name']]);
        }

        if ($request->has('username')) {
        $updateData['username'] = $request->username ? trim($request->username) : null;
    }

        if ($avatarUrl !== $user->avatar_url) {
            $updateData['avatar_url'] = $avatarUrl;
        }

        // 🔍 デバッグ: 更新データを確認
        \Log::info('🔍 更新データ:', $updateData);

        if (!empty($updateData)) {
            $user->update($updateData);
            
            // 🔧 更新後のデータを確認
            $user->refresh(); // データベースから最新データを再読み込み
            \Log::info('✅ ユーザー更新完了:', [
                'updated_fields' => array_keys($updateData),
                'current_name' => $user->name,
                'current_avatar' => $user->avatar_url
            ]);
        } else {
            \Log::info('⚠️ 更新データなし');
        }

        // 統計情報を再読み込み
        $user->loadCount(['recipeLikes', 'recipeComments', 'likedRecipes', 'recipes']);

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'data' => new UserProfileResource($user), // refreshされた最新データを返す
        ]);
    }

    /**
     * 🔧 アバター画像アップロード処理（シンプル版・Intervention Image不要）
     */
    private function handleAvatarUpload($file)
    {
        try {
            // ファイル情報をログに記録
            \Log::info('📷 handleAvatarUpload 開始:', [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'temp_path' => $file->getPathname()
            ]);

            // セキュアなファイル名を生成
            $extension = $file->getClientOriginalExtension();
            $filename = time() . '_' . Str::random(10) . '.' . $extension;
            
            // avatarsディレクトリに保存
            $path = $file->storeAs('avatars', $filename, 'public');
            
            if (!$path) {
                throw new \Exception('ファイルの保存に失敗しました');
            }

            // 保存されたファイルの存在確認
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('ファイルが正常に保存されませんでした');
            }

            // 公開URLを生成
            $avatarUrl = '/storage/' . $path;
            
            \Log::info('📷 アバター画像処理完了:', [
                'original_name' => $file->getClientOriginalName(),
                'saved_path' => $path,
                'public_url' => $avatarUrl,
                'file_size' => $file->getSize(),
                'saved_file_size' => Storage::disk('public')->size($path)
            ]);
            
            return $avatarUrl;
            
        } catch (\Exception $e) {
            \Log::error('❌ handleAvatarUpload エラー:', [
                'error' => $e->getMessage(),
                'file_name' => $file->getClientOriginalName(),
                'file_size' => $file->getSize(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new \Exception('画像のアップロード処理中にエラーが発生しました: ' . $e->getMessage());
        }
    }

    /**
     * プロフィール統計情報
     */
    public function stats()
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        // 統計情報を読み込み
        $user->loadCount(['recipeLikes', 'recipeComments', 'recipes']);

        // ユーザーの活動統計
        $stats = [
            'total_likes' => $user->recipe_likes_count ?? 0,
            'total_comments' => $user->recipe_comments_count ?? 0,
            'member_since' => $user->created_at->format('Y年m月'),
            'days_since_joined' => $user->created_at->diffInDays(now()),
        ];

        // 管理者の場合は投稿レシピ数も追加
        if ($user->isAdmin()) {
            $stats['total_recipes'] = $user->recipes_count ?? 0;
            $stats['published_recipes'] = $user->recipes()->where('is_published', true)->count();
        }

        return response()->json([
            'data' => $stats
        ]);
    }

    /**
     * アカウント削除（論理削除）
     */
    public function destroy(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        // パスワード確認などの追加認証が必要な場合はここに実装

        try {
            // アバター画像を削除
            if ($user->avatar_url && $user->avatar_url !== '/images/default-avatar.png') {
                $imagePath = str_replace('/storage/', '', $user->avatar_url);
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }

            // ユーザー情報を匿名化して論理削除
            $user->update([
                'name' => '削除されたユーザー',
                'email' => 'deleted_' . time() . '@example.com',
                'username' => null,
                'avatar_url' => null,
                'firebase_uid' => null,
            ]);

            return response()->json([
                'message' => 'アカウントを削除しました'
            ]);

        } catch (\Exception $e) {
            \Log::error('Account deletion failed: ' . $e->getMessage());
            return response()->json([
                'message' => 'アカウントの削除に失敗しました'
            ], 500);
        }
    }

    // 既存のクラス内に以下のメソッドを追加
    public function avatar($filename)
    {
        $path = storage_path('app/public/avatars/' . $filename);
        
        if (!file_exists($path)) {
            abort(404, 'Image not found');
        }
        
        $mimeType = mime_content_type($path);
        
        return Response::file($path, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'public, max-age=3600'
        ]);
    }
}
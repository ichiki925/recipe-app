<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class ProfileController extends Controller
{
    /**
     * ユーザープロフィール表示
     */
    public function show(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        return response()->json([
            'data' => [
                'id' => $user->id,
                'firebase_uid' => $user->firebase_uid,
                'name' => $user->name,
                'email' => $user->email,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'avatar' => $user->avatar,
                'role' => $user->role,
                'is_admin' => $user->isAdmin(),
                'is_user' => $user->isUser(),
                'email_verified_at' => $user->email_verified_at,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * ユーザープロフィール更新
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'message' => 'ログインが必要です'
            ], 401);
        }

        // バリデーション
        $request->validate([
            'name' => 'required|string|min:2|max:20',
            'username' => 'nullable|string|min:2|max:20|unique:users,username,' . $user->id,
            'avatar' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120', // 5MB制限
        ], [
            'name.required' => 'ユーザーネームを入力してください',
            'name.min' => 'ユーザーネームは2文字以上で入力してください',
            'name.max' => 'ユーザーネームは20文字以内で入力してください',
            'username.min' => 'ユーザーネームは2文字以上で入力してください',
            'username.max' => 'ユーザーネームは20文字以内で入力してください',
            'username.unique' => 'このユーザーネームは既に使用されています',
            'avatar.image' => '画像ファイルを選択してください',
            'avatar.mimes' => '対応している形式: JPEG, PNG, GIF, WebP',
            'avatar.max' => 'ファイルサイズは5MB以下にしてください',
        ]);

        // 追加のユーザーネームバリデーション
        if ($request->has('name')) {
            $name = trim($request->name);

            // 使用可能文字のチェック
            if (!preg_match('/^[a-zA-Z0-9\u3040-\u309F\u30A0-\u30FF\u4E00-\u9FAF_\-\s]+$/u', $name)) {
                return response()->json([
                    'message' => '使用できない文字が含まれています',
                    'errors' => [
                        'name' => ['使用できない文字が含まれています']
                    ]
                ], 422);
            }

            // 連続するスペースのチェック
            if (preg_match('/\s{2,}/', $name)) {
                return response()->json([
                    'message' => '連続するスペースは使用できません',
                    'errors' => [
                        'name' => ['連続するスペースは使用できません']
                    ]
                ], 422);
            }
        }

        // アバター画像のアップロード処理
        $avatarUrl = $user->avatar_url;
        if ($request->hasFile('avatar')) {
            try {
                // 古いアバター画像を削除
                if ($user->avatar_url && $user->avatar_url !== '/images/default-avatar.png') {
                    $oldImagePath = str_replace('/storage/', '', $user->avatar_url);
                    Storage::disk('public')->delete($oldImagePath);
                }

                // 新しいアバター画像をアップロード
                $avatarUrl = $this->handleAvatarUpload($request->file('avatar'));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'アバター画像のアップロードに失敗しました',
                    'errors' => [
                        'avatar' => ['画像のアップロードに失敗しました']
                    ]
                ], 422);
            }
        }

        // ユーザー情報を更新
        $updateData = [
            'name' => trim($request->name),
        ];

        if ($request->has('username')) {
            $updateData['username'] = $request->username ? trim($request->username) : null;
        }

        if ($avatarUrl !== $user->avatar_url) {
            $updateData['avatar_url'] = $avatarUrl;
        }

        $user->update($updateData);

        return response()->json([
            'message' => 'プロフィールを更新しました',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'avatar_url' => $user->avatar_url,
                'avatar' => $user->avatar,
                'updated_at' => $user->updated_at,
            ]
        ]);
    }

    /**
     * アバター画像のアップロード処理
     */
    private function handleAvatarUpload($uploadedFile)
    {
        try {
            // ファイル名生成
            $filename = 'avatar_' . auth()->id() . '_' . time() . '.jpg';

            // 画像を読み込み、リサイズ、圧縮
            $image = Image::make($uploadedFile)
                ->orientate() // EXIF回転情報を適用
                ->fit(200, 200) // 正方形にクロップ＆リサイズ
                ->encode('jpg', 90); // JPEG 90%品質で圧縮

            // ストレージに保存
            $path = 'avatars/' . $filename;
            Storage::disk('public')->put($path, $image);

            return '/storage/' . $path;

        } catch (\Exception $e) {
            \Log::error('Avatar upload failed: ' . $e->getMessage());
            throw new \Exception('アバター画像のアップロードに失敗しました');
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

        // ユーザーの活動統計
        $stats = [
            'total_likes' => $user->recipeLikes()->count(),
            'total_comments' => $user->recipeComments()->count(),
            'member_since' => $user->created_at->format('Y年m月'),
            'days_since_joined' => $user->created_at->diffInDays(now()),
        ];

        // 管理者の場合は投稿レシピ数も追加
        if ($user->isAdmin()) {
            $stats['total_recipes'] = $user->recipes()->count();
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
                Storage::disk('public')->delete($imagePath);
            }

            // 関連データの処理（必要に応じて）
            // - いいねは残す（匿名化）
            // - コメントは残す（匿名化）

            // ユーザー情報を匿名化して論理削除
            $user->update([
                'name' => '削除されたユーザー',
                'email' => 'deleted_' . time() . '@example.com',
                'username' => null,
                'avatar_url' => null,
                'firebase_uid' => null,
            ]);

            // 実際の削除処理（SoftDeletesを使用している場合）
            // $user->delete();

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
}
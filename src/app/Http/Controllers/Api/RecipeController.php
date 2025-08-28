<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\RecipeCollection;
use App\Http\Resources\AdminRecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Intervention\Image\ImageManagerStatic as Image;

class RecipeController extends Controller
{
    private const IMAGE_DIRECTORY = 'recipe_images';

    public function index(Request $request)
    {
        \Log::info('=== Public Recipe Index (未ログイン向け) ===');

        $query = Recipe::with(['admin'])
                    ->published()
                    ->withCount('likes');

        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                ->orWhere('ingredients', 'LIKE', "%{$keyword}%");
            });
        }

        $recipes = $query->latest()->paginate(9);

        $recipesData = $recipes->getCollection()->map(function ($recipe) {
            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'genre' => $recipe->genre,
                'likes_count' => $recipe->likes_count ?? 0,
                'image_url' => $recipe->image_url,
                'is_liked' => false,
                'admin' => [
                    'id' => $recipe->admin->id,
                    'name' => $recipe->admin->name
                ]
            ];
        });

        return response()->json([
            'current_page' => $recipes->currentPage(),
            'data' => $recipesData,
            'last_page' => $recipes->lastPage(),
            'per_page' => $recipes->perPage(),
            'total' => $recipes->total()
        ]);
    }

    public function userIndex(Request $request)
    {
        $user = $request->user();

        \Log::info('=== User Recipe Index (ログインユーザー向け) ===', [
            'user_id' => $user->id,
            'user_email' => $user->email,
            'is_admin' => $user->isAdmin()
        ]);

        $query = Recipe::with(['admin'])
                    ->published()
                    ->withCount('likes');

        // 検索機能
        if ($request->has('keyword') && !empty($request->keyword)) {
            $keyword = $request->keyword;
            $query->where(function($q) use ($keyword) {
                $q->where('title', 'LIKE', "%{$keyword}%")
                ->orWhere('ingredients', 'LIKE', "%{$keyword}%");
            });
        }

        $recipes = $query->latest()->paginate(9);

        $recipesWithLikeStatus = $recipes->getCollection()->map(function ($recipe) use ($user) {
            $isLiked = false;

            if ($user->isAdmin()) {
                \Log::info("Recipe {$recipe->id}: ユーザーは管理者のためis_liked=false");
                $isLiked = false;
            } else {
                $likeExists = \DB::table('recipe_likes')
                    ->where('user_id', $user->id)
                    ->where('recipe_id', $recipe->id)
                    ->exists();

                $isLiked = $likeExists;

                \Log::info("Recipe {$recipe->id} いいね状態", [
                    'user_id' => $user->id,
                    'recipe_id' => $recipe->id,
                    'is_liked' => $isLiked
                ]);
            }

            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'genre' => $recipe->genre,
                'likes_count' => $recipe->likes_count ?? 0,
                'image_url' => $recipe->image_url,
                'is_liked' => $isLiked,
                'admin' => [
                    'id' => $recipe->admin->id,
                    'name' => $recipe->admin->name
                ]
            ];
        });

        return response()->json([
            'current_page' => $recipes->currentPage(),
            'data' => $recipesWithLikeStatus,
            'last_page' => $recipes->lastPage(),
            'per_page' => $recipes->perPage(),
            'total' => $recipes->total()
        ]);
    }



    public function search(Request $request)
    {
        try {
            $keyword = $request->get('keyword', '');
            $perPage = $request->get('per_page', 9);
            $user = $request->user();

            \Log::info('Recipe search started', [
                'keyword' => $keyword,
                'page' => $request->get('page', 1),
                'is_authenticated' => $user ? true : false,
                'user_id' => $user ? $user->id : null,
                'is_admin' => $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false
            ]);

            $query = Recipe::published()
                ->with('admin')
                ->withCount('likes');

            if (!empty($keyword)) {
                $query->where(function($q) use ($keyword) {
                    $q->where('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('title_reading', 'LIKE', "%{$keyword}%")
                        ->orWhere('ingredients', 'LIKE', "%{$keyword}%");
                });
            }

            $recipes = $query->latest()->paginate($perPage);

            $recipesData = collect($recipes->items())->map(function($recipe) use ($user) {
                $isLiked = false;

                if ($user) {
                    if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
                        $isLiked = false;
                        \Log::debug("Recipe {$recipe->id}: 管理者のためis_liked=false");
                    } else {
                        $isLiked = \DB::table('recipe_likes')
                            ->where('user_id', $user->id)
                            ->where('recipe_id', $recipe->id)
                            ->exists();

                        \Log::debug("Recipe {$recipe->id}: ユーザー{$user->id}のいいね状態={$isLiked}");
                    }
                } else {
                    $isLiked = false;
                    \Log::debug("Recipe {$recipe->id}: 未認証のためis_liked=false");
                }

                return [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'likes_count' => $recipe->likes_count ?? 0,
                    'image_url' => $recipe->image_url,
                    'is_liked' => $isLiked,
                    'admin' => [
                        'id' => $recipe->admin->id,
                        'name' => $recipe->admin->name
                    ]
                ];
            });

            return response()->json([
                'current_page' => $recipes->currentPage(),
                'data' => $recipesData,
                'last_page' => $recipes->lastPage(),
                'per_page' => $recipes->perPage(),
                'total' => $recipes->total()
            ]);

        } catch (\Exception $e) {
            \Log::error('Recipe search error', [
                'error' => $e->getMessage(),
                'keyword' => $request->get('keyword', ''),
                'user_id' => $request->user() ? $request->user()->id : null,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'current_page' => 1,
                'data' => [],
                'last_page' => 1,
                'per_page' => 9,
                'total' => 0
            ]);
        }
    }

    public function create()
    {
        return response()->json(['message' => 'Use POST /admin/recipes to create recipe']);
    }


    public function store(Request $request)
    {
        try {
            \Log::info('=== Recipe store method START ===');

            // Firebase認証ユーザーを取得
            $user = $request->user();

            if (!$user || !$user->isAdmin()) {
                return response()->json(['error' => '認証または権限エラー'], 403);
            }

            // バリデーション
            $request->validate([
                'title' => 'required|string|max:255',
                'servings' => 'required|string',
                'ingredients' => 'required|string',
                'instructions' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:10240'
            ]);

            \Log::info('Validation passed');

            // 画像アップロード処理（統一されたメソッドを使用）
            $imageUrl = null;
            if ($request->hasFile('image')) {
                try {
                    \Log::info('Image upload starting...');
                    $imageUrl = $this->handleImageUploadSecure($request->file('image'));
                    \Log::info('Image saved successfully:', ['final_url' => $imageUrl]);
                } catch (\Exception $e) {
                    \Log::error('Image upload failed: ' . $e->getMessage());
                    $imageUrl = null;
                }
            }

            // レシピ作成
            $recipe = Recipe::create([
                'title' => $request->title,
                'genre' => $request->genre ?? '',
                'servings' => $request->servings,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'image_url' => $imageUrl,
                'admin_id' => $user->id,
                'is_published' => true,
                'views_count' => 0,
                'likes_count' => 0
            ]);

            \Log::info('Recipe created successfully:', [
                'recipe_id' => $recipe->id,
                'admin_id' => $recipe->admin_id,
                'saved_image_url' => $recipe->image_url
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが投稿されました',
                'data' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'genre' => $recipe->genre,
                    'servings' => $recipe->servings,
                    'image_url' => $recipe->image_url,
                    'created_at' => $recipe->created_at->format('Y-m-d H:i:s')
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation error:', ['errors' => $e->errors()]);
            return response()->json([
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Recipe store error:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの作成に失敗しました: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Recipe $recipe)
    {
        // 公開されていないレシピは表示しない
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'レシピが見つかりません'
            ], 404);
        }

        // 必要なリレーションを読み込み
        $recipe->load(['admin', 'comments.user']);

        // 閲覧数を増加
        $recipe->incrementViews();

        // RecipeResourceで変換して返す
        return new RecipeResource($recipe);
    }


    public function edit($id)
    {
        return response()->json(['message' => 'Use GET /admin/recipes/{id} to get recipe data']);
    }


    public function update(Request $request, Recipe $recipe)
    {
        try {
            // 📝 本番用：簡潔なログ
            \Log::info('Recipe update started', [
                'recipe_id' => $recipe->id,
                'admin_id' => auth()->id()
            ]);

            $request->validate([
                'title' => 'required|string|max:255',
                'genre' => 'nullable|string|max:100',
                'servings' => 'required|in:1人分,2人分,3人分,4人分,5人分以上',
                'ingredients' => 'required|string',
                'instructions' => 'required|string',
                'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,heic,webp|max:10240',
                'is_published' => 'boolean'
            ]);

            // 画像更新処理
            if ($request->hasFile('image')) {
                // 🔒 セキュリティ強化された画像アップロード
                $newImageUrl = $this->handleImageUploadSecure($request->file('image'));

                // 古い画像削除
                if ($recipe->image_url && $recipe->image_url !== '/images/no-image.png') {
                    $this->deleteOldImage($recipe->image_url);
                }

                $recipe->image_url = $newImageUrl;
            }

            // レシピデータ更新
            $recipe->update([
                'title' => $request->title,
                'genre' => $request->genre,
                'servings' => $request->servings,
                'ingredients' => $request->ingredients,
                'instructions' => $request->instructions,
                'is_published' => $request->get('is_published', $recipe->is_published)
            ]);

            $recipe->load(['admin', 'comments', 'likes']);

            // 📝 成功時は簡潔なログ
            \Log::info('Recipe updated successfully', [
                'recipe_id' => $recipe->id
            ]);

            return response()->json([
                'message' => 'レシピが更新されました',
                'data' => new AdminRecipeResource($recipe)
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // 📝 バリデーションエラーは詳細に
            \Log::warning('Recipe update validation failed', [
                'recipe_id' => $recipe->id,
                'errors' => $e->errors()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'バリデーションエラー',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            // 📝 エラー時は詳細なログ
            \Log::error('Recipe update failed', [
                'recipe_id' => $recipe->id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの更新に失敗しました'
            ], 500);
        }
    }


    public function destroy(Recipe $recipe)
    {
        // 統一されたメソッドを使用
        if ($recipe->image_url) {
            $this->deleteOldImage($recipe->image_url);
        }

        $recipe->delete();

        return response()->json([
            'message' => 'レシピが削除されました'
        ]);
    }

    public function adminDestroy($id)
    {
        try {
            \Log::info('=== Admin Recipe Delete START ===', [
                'recipe_id' => $id,
                'timestamp' => now(),
                'user_id' => auth()->id() ?? 'not_authenticated'
            ]);

            // 認証確認
            $user = request()->user();
            if (!$user) {
                \Log::warning('Delete attempt without authentication');
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                \Log::warning('Delete attempt by non-admin user', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            // レシピを取得（削除済みも含める）
            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                \Log::warning('Recipe not found for deletion', ['recipe_id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            \Log::info('Recipe found for deletion', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title,
                'is_already_deleted' => $recipe->trashed()
            ]);

            // 関連データを削除（コメント、いいねなど）
            if ($recipe->comments()->exists()) {
                $commentsCount = $recipe->comments()->count();
                $recipe->comments()->delete();
                \Log::info('Comments deleted', ['count' => $commentsCount]);
            }

            if ($recipe->likes()->exists()) {
                $likesCount = $recipe->likes()->count();
                $recipe->likes()->delete();
                \Log::info('Likes deleted', ['count' => $likesCount]);
            }

            // 統一されたメソッドを使用
            if ($recipe->image_url) {
                $this->deleteOldImage($recipe->image_url);
            }

            // レシピを完全削除
            if ($recipe->trashed()) {
                $recipe->forceDelete(); // 既にソフトデリートされている場合は完全削除
                \Log::info('Recipe force deleted');
            } else {
                $recipe->delete(); // ソフトデリート
                \Log::info('Recipe soft deleted');
            }

            \Log::info('=== Admin Recipe Delete COMPLETED ===', [
                'recipe_id' => $id,
                'success' => true
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピが正常に削除されました',
                'deleted_id' => $id
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Admin recipe deletion failed', [
                'recipe_id' => $id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの削除に失敗しました',
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }


    public function adminIndex(Request $request)
    {
        try {
            \Log::info('=== Admin Index START ===', [
                'request_params' => $request->all(),
                'timestamp' => now()
            ]);

            $query = Recipe::with(['admin', 'comments.user'])
                            ->withCount(['comments', 'likes']);

            // 検索
            if ($request->filled('keyword')) {
                $keyword = $request->keyword;
                $query->where(function($q) use ($keyword) {
                    $q->where('title', 'LIKE', "%{$keyword}%")
                        ->orWhere('title_reading', 'LIKE', "%{$keyword}%")
                        ->orWhere('ingredients', 'LIKE', "%{$keyword}%")
                        ->orWhere('genre', 'LIKE', "%{$keyword}%");
                });
                \Log::info('Search applied', ['keyword' => $keyword]);
            }

            $recipes = $query->orderBy('updated_at', 'desc')->paginate(9);

            \Log::info('Recipes retrieved', [
                'total' => $recipes->total(),
                'current_page' => $recipes->currentPage(),
                'last_page' => $recipes->lastPage(),
                'per_page' => $recipes->perPage()
            ]);

            // カスタムレスポンス形式で返す
            return response()->json([
                'current_page' => $recipes->currentPage(),
                'data' => AdminRecipeResource::collection($recipes->items()),
                'first_page_url' => $recipes->url(1),
                'from' => $recipes->firstItem(),
                'last_page' => $recipes->lastPage(),
                'last_page_url' => $recipes->url($recipes->lastPage()),
                'links' => [
                    [
                        'url' => $recipes->previousPageUrl(),
                        'label' => '&laquo; Previous',
                        'active' => false
                    ],
                    [
                        'url' => $recipes->url($recipes->currentPage()),
                        'label' => (string) $recipes->currentPage(),
                        'active' => true
                    ],
                    [
                        'url' => $recipes->nextPageUrl(),
                        'label' => 'Next &raquo;',
                        'active' => false
                    ]
                ],
                'next_page_url' => $recipes->nextPageUrl(),
                'path' => $recipes->path(),
                'per_page' => $recipes->perPage(),
                'prev_page_url' => $recipes->previousPageUrl(),
                'to' => $recipes->lastItem(),
                'total' => $recipes->total()
            ]);

        } catch (\Exception $e) {
            \Log::error('Admin index error', [
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピ一覧の取得に失敗しました'
            ], 500);
        }
    }


    public function restore($id)
    {
        try {
            \Log::info('=== Recipe Restore START ===', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'timestamp' => now()
            ]);

            // 認証確認
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            // 削除済みレシピを取得
            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            if (!$recipe->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'このレシピは削除されていません'
                ], 422);
            }

            // レシピを復元し、updated_atを現在時刻に更新
            $recipe->restore();
            $recipe->touch(); // updated_atを現在時刻に更新

            \Log::info('Recipe restored successfully', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title,
                'admin_id' => $user->id,
                'updated_at' => $recipe->updated_at
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピを復元しました',
                'data' => [
                    'id' => $recipe->id,
                    'title' => $recipe->title,
                    'restored_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Recipe restore failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの復元に失敗しました'
            ], 500);
        }
    }




    public function adminShow($id)
    {
        try {

            // 📝 簡潔なログ
            \Log::info('Admin show recipe', [
                'recipe_id' => $id,
                'admin_id' => auth()->id()
            ]);

            // 認証チェック（詳細なエラー分離を維持）
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            // レシピ取得（並び順を維持）
            $recipe = Recipe::withTrashed()
                ->with([
                    'admin',
                    'comments' => function($query) {
                        $query->with('user')->orderBy('created_at', 'desc');
                    },
                    'likes' => function($query) {
                        $query->with('user')->orderBy('created_at', 'desc');
                    }
                ])
                ->find($id);

                if (!$recipe) {
                    return response()->json([
                        'status' => 'error',
                        'message' => 'レシピが見つかりません'
                    ], 404);
                }


                return response()->json([
                    'status' => 'success',
                    'message' => 'レシピを取得しました',
                    'data' => new AdminRecipeResource($recipe)
                ]);


        } catch (\Exception $e) {
            // 📝 エラー時は必要な情報のみ
            \Log::error('Admin show recipe failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの取得に失敗しました'
            ], 500);
        }
    }



    /**
     * レシピを完全削除
     */
    public function permanentDelete($id)
    {
        try {
            \Log::info('=== Recipe Permanent Delete START ===', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'timestamp' => now()
            ]);

            // 認証確認
            $user = request()->user();
            if (!$user) {
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            // 削除済みレシピを取得
            $recipe = Recipe::withTrashed()->find($id);

            if (!$recipe) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            if (!$recipe->trashed()) {
                return response()->json([
                    'status' => 'error',
                    'message' => '先に論理削除してから完全削除してください'
                ], 422);
            }

            // 関連データを完全削除
            if ($recipe->comments()->withTrashed()->exists()) {
                $commentsCount = $recipe->comments()->withTrashed()->count();
                $recipe->comments()->withTrashed()->forceDelete();
                \Log::info('Comments permanently deleted', ['count' => $commentsCount]);
            }

            if ($recipe->likes()->exists()) {
                $likesCount = $recipe->likes()->count();
                $recipe->likes()->delete();
                \Log::info('Likes permanently deleted', ['count' => $likesCount]);
            }

            // 画像ファイルを削除
            if ($recipe->image_url) {
                try {
                    $imagePath = str_replace('/storage/', '', $recipe->image_url);
                    if (Storage::disk('public')->exists($imagePath)) {
                        Storage::disk('public')->delete($imagePath);
                        \Log::info('Image file permanently deleted', ['path' => $imagePath]);
                    }
                } catch (\Exception $e) {
                    \Log::error('Image deletion failed', ['error' => $e->getMessage()]);
                }
            }

            $recipeTitle = $recipe->title;

            // レシピを完全削除
            $recipe->forceDelete();

            \Log::info('Recipe permanently deleted successfully', [
                'recipe_id' => $id,
                'recipe_title' => $recipeTitle,
                'admin_id' => $user->id
            ]);

            return response()->json([
                'status' => 'success',
                'message' => 'レシピを完全に削除しました',
                'data' => [
                    'deleted_id' => $id,
                    'title' => $recipeTitle,
                    'deleted_at' => now()->format('Y-m-d H:i:s')
                ]
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Recipe permanent delete failed', [
                'recipe_id' => $id,
                'admin_id' => auth()->id(),
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの完全削除に失敗しました'
            ], 500);
        }
    }

    private function handleImageUploadSecure($uploadedFile)
    {
        try {
            // 📏 ファイルサイズチェック（5MB制限）
            $maxSize = 5 * 1024 * 1024; // 5MB
            if ($uploadedFile->getSize() > $maxSize) {
                throw new \Exception('ファイルサイズが大きすぎます（5MB以下にしてください）');
            }

            // 🛡️ MIMEタイプの厳密チェック
            $allowedMimes = [
                'image/jpeg',
                'image/png', 
                'image/gif',
                'image/webp'
            ];

            if (!in_array($uploadedFile->getMimeType(), $allowedMimes)) {
                throw new \Exception('許可されていないファイル形式です');
            }

            // 📝 拡張子とMIMEタイプの整合性チェック
            $extension = strtolower($uploadedFile->getClientOriginalExtension());
            $mimeType = $uploadedFile->getMimeType();

            $validCombinations = [
                'jpg' => ['image/jpeg'],
                'jpeg' => ['image/jpeg'],
                'png' => ['image/png'],
                'gif' => ['image/gif'],
                'webp' => ['image/webp']
            ];

            if (!isset($validCombinations[$extension]) || 
                !in_array($mimeType, $validCombinations[$extension])) {
                throw new \Exception('ファイル拡張子とファイル形式が一致しません');
            }

            // 🔐 ファイル名のサニタイズ（予測困難な名前生成）
            $timestamp = time();
            $randomString = bin2hex(random_bytes(8)); // より安全なランダム文字列
            $filename = $timestamp . '_' . $randomString . '.' . $extension;

            // 📁 安全なディレクトリ保存
            $path = $uploadedFile->storeAs(self::IMAGE_DIRECTORY, $filename, 'public');


            // ✅ ファイル保存確認
            if (!Storage::disk('public')->exists($path)) {
                throw new \Exception('ファイルの保存に失敗しました');
            }

            return '/storage/' . $path;

        } catch (\Exception $e) {
            \Log::error('Secure image upload failed', [
                'error' => $e->getMessage(),
                'file_size' => $uploadedFile->getSize(),
                'mime_type' => $uploadedFile->getMimeType(),
                'extension' => $uploadedFile->getClientOriginalExtension()
            ]);
            throw $e;
        }
    }

    private function deleteOldImage($imageUrl)
    {
        try {
            $oldImagePath = str_replace('/storage/', '', $imageUrl);

            if (Storage::disk('public')->exists($oldImagePath)) {
                Storage::disk('public')->delete($oldImagePath);
                \Log::info('Old image deleted', ['path' => $oldImagePath]);
            }
        } catch (\Exception $e) {
            \Log::warning('Old image deletion failed', [
                'path' => $oldImagePath ?? 'unknown',
                'error' => $e->getMessage()
            ]);
            // 古い画像削除の失敗は致命的ではないので、例外を再発生させない
        }
    }

}

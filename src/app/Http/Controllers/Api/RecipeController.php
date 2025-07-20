<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Http\Resources\RecipeResource;
use App\Http\Resources\RecipeCollection;
use App\Http\Resources\AdminRecipeResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class RecipeController extends Controller
{

    public function index(Request $request)
    {
        $query = Recipe::with('admin')->published();

        // 検索機能
        if ($request->has('search')) {
            $query->search($request->search);
        }

        // ジャンル絞り込み
        if ($request->has('genre')) {
            $query->byGenre($request->genre);
        }

        // ソート
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'popular':
                $query->popular();
                break;
            case 'views':
                $query->orderBy('views_count', 'desc');
                break;
            default:
                $query->latest();
                break;
        }

        $recipes = $query->paginate(20);

        return new RecipeCollection($recipes);
    }

    public function search(Request $request)
    {
        $keyword = $request->get('keyword', '');

        // 🔍 空キーワードなら全件を返す
        if (empty($keyword)) {
            return Recipe::published()
                ->with('admin')
                ->latest()
                ->paginate(6);
        }

        // 🔍 キーワードありの場合は検索して返す
        $recipes = Recipe::published()
            ->with('admin')
            ->search($keyword)
            ->latest()
            ->paginate(6);

        return response()->json($recipes);
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

            // 画像アップロード処理（修正版）
            $imageUrl = null;
            if ($request->hasFile('image')) {
                try {
                    \Log::info('Image upload starting...');

                    $image = $request->file('image');

                    // ファイル名を一度だけ生成（重要な修正）
                    $timestamp = time();
                    $uniqueId = uniqid();
                    $extension = $image->getClientOriginalExtension();
                    $filename = $timestamp . '_' . $uniqueId . '.' . $extension;

                    \Log::info('Generated filename:', [
                        'timestamp' => $timestamp,
                        'unique_id' => $uniqueId,
                        'extension' => $extension,
                        'full_filename' => $filename
                    ]);

                    // ファイル保存
                    $path = $image->storeAs('recipes', $filename, 'public');
                    $imageUrl = '/storage/' . $path;

                    \Log::info('Image saved successfully:', [
                        'storage_path' => $path,
                        'final_url' => $imageUrl,
                        'actual_saved_filename' => basename($path)
                    ]);

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
            // 古い画像削除
            if ($recipe->image_url) {
                $oldImagePath = str_replace('/storage/', '', $recipe->image_url);
                Storage::disk('public')->delete($oldImagePath);
            }

            // 新しい画像アップロード
            $recipe->image_url = $this->handleImageUpload($request->file('image'));
        }

        $recipe->update([
            'title' => $request->title,
            'genre' => $request->genre,
            'servings' => $request->servings,
            'ingredients' => $request->ingredients,
            'instructions' => $request->instructions,
            'is_published' => $request->get('is_published', $recipe->is_published)
        ]);

        $recipe->load(['admin', 'comments', 'likes']);


        return response()->json([
            'message' => 'レシピが更新されました',
            'data' => new AdminRecipeResource($recipe)
        ]);
    }


    public function destroy(Recipe $recipe)
    {
        // 画像削除
        if ($recipe->image_url) {
            $imagePath = str_replace('/storage/', '', $recipe->image_url);
            Storage::disk('public')->delete($imagePath);
        }

        $recipe->delete();

        return response()->json([
            'message' => 'レシピが削除されました'
        ]);
    }

    public function adminIndex(Request $request)
    {
        $query = Recipe::with(['admin', 'comments', 'likes'])->withTrashed();


        // 検索
        if ($request->has('keyword')) {
            $query->search($request->keyword);
        }

        // 公開状態フィルター
        if ($request->has('status')) {
            switch ($request->status) {
                case 'published':
                    $query->where('is_published', true);
                    break;
                case 'draft':
                    $query->where('is_published', false);
                    break;
                case 'deleted':
                    $query->onlyTrashed();
                    break;
            }
        }

        $recipes = $query->latest()->paginate(12);

        return AdminRecipeResource::collection($recipes);

    }

    public function adminShow($id)
    {
        try {
            \Log::info('=== Admin Show Method START ===', [
                'recipe_id' => $id,
                'timestamp' => now(),
                'user_id' => auth()->id() ?? 'not_authenticated'
            ]);

            // 認証ユーザーの確認
            $user = request()->user();
            if (!$user) {
                \Log::warning('No authenticated user found');
                return response()->json([
                    'status' => 'error',
                    'message' => '認証が必要です'
                ], 401);
            }

            if (!$user->isAdmin()) {
                \Log::warning('User is not admin', ['user_id' => $user->id]);
                return response()->json([
                    'status' => 'error', 
                    'message' => '管理者権限が必要です'
                ], 403);
            }

            \Log::info('Authentication passed', [
                'user_id' => $user->id,
                'user_role' => $user->role ?? 'unknown'
            ]);

            // レシピを取得（削除済みも含む、必要なリレーションを含む）
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
                \Log::warning('Recipe not found', ['recipe_id' => $id]);
                return response()->json([
                    'status' => 'error',
                    'message' => 'レシピが見つかりません'
                ], 404);
            }

            \Log::info('Recipe loaded successfully', [
                'recipe_id' => $recipe->id,
                'recipe_title' => $recipe->title,
                'admin_loaded' => $recipe->relationLoaded('admin'),
                'comments_loaded' => $recipe->relationLoaded('comments'),
                'comments_count' => $recipe->comments ? $recipe->comments->count() : 0,
                'likes_count' => $recipe->likes ? $recipe->likes->count() : 0,
                'is_published' => $recipe->is_published,
                'is_deleted' => $recipe->trashed()
            ]);

            // AdminRecipeResourceを使用してレスポンス作成
            $resourceData = new AdminRecipeResource($recipe);

            \Log::info('Resource created successfully');

            return response()->json([
                'status' => 'success',
                'message' => 'レシピを取得しました',
                'data' => $resourceData
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            \Log::error('Recipe not found exception', [
                'recipe_id' => $id,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'status' => 'error',
                'message' => 'レシピが見つかりません'
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Admin show error', [
                'recipe_id' => $id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'レシピの取得に失敗しました',
                'debug_info' => config('app.debug') ? [
                    'error' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine()
                ] : null
            ], 500);
        }
    }

    private function handleImageUpload($uploadedFile)
    {
        try {
            // ファイル名生成
            $filename = time() . '_' . uniqid() . '.jpg';

            // 画像を読み込み、リサイズ、圧縮
            $image = Image::make($uploadedFile)
                ->orientate() // EXIF回転情報を適用
                ->resize(800, 600, function ($constraint) {
                    $constraint->aspectRatio(); // アスペクト比維持
                    $constraint->upsize(); // 元画像より大きくしない
                })
                ->encode('jpg', 85); // JPEG 85%品質で圧縮

            // ストレージに保存
            $path = 'recipes/' . $filename;
            Storage::disk('public')->put($path, $image);

            return '/storage/' . $path;

        } catch (\Exception $e) {
            \Log::error('Image upload failed: ' . $e->getMessage());
            throw new \Exception('画像のアップロードに失敗しました');
        }
    }
}

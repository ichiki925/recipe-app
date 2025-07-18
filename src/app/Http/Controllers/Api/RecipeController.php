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
        $request->validate([
            'title' => 'required|string|max:255',
            'genre' => 'nullable|string|max:100',
            'servings' => 'required|in:1人分,2人分,3人分,4人分,5人分以上',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,jpg,png,gif,heic,webp|max:10240',
            'is_published' => 'boolean'
        ], [
            'title.required' => '料理名は必須です',
            'servings.required' => '人数を選択してください',
            'ingredients.required' => '材料は必須です',
            'instructions.required' => '作り方は必須です',
            'image.image' => '画像ファイルを選択してください',
            'image.max' => '画像サイズは10MB以下にしてください'
        ]);

        $imageUrl = null;
        
        // 画像アップロード処理
        if ($request->hasFile('image')) {
            $imageUrl = $this->handleImageUpload($request->file('image'));
        }

        $recipe = Recipe::create([
            'title' => $request->title,
            'genre' => $request->genre,
            'servings' => $request->servings,
            'ingredients' => $request->ingredients,
            'instructions' => $request->instructions,
            'image_url' => $imageUrl,
            'admin_id' => auth()->id(),
            'is_published' => $request->get('is_published', true)
        ]);

        $recipe->load(['admin', 'comments', 'likes']);


        return response()->json([
            'message' => 'レシピが投稿されました',
            'data' => new AdminRecipeResource($recipe)
        ], 201);
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

    public function adminShow(Recipe $recipe)
    {
        $recipe->load(['admin', 'comments.user', 'likes.user']);

        return response()->json([
            'data' => new AdminRecipeResource($recipe)
        ]);
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

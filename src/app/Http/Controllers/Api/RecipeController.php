<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class RecipeController extends Controller
{

    public function index(Request $request)
    {
        $query = Recipe::published()->with('admin');

        // 検索キーワード
        if ($request->has('keyword')) {
            $query->search($request->keyword);
        }

        // ジャンル別
        if ($request->has('genre')) {
            $query->byGenre($request->genre);
        }

        // ソート
        $sortBy = $request->get('sort', 'latest');
        switch ($sortBy) {
            case 'popular':
                $query->popular();
                break;
            case 'latest':
            default:
                $query->latest();
                break;
        }

        $recipes = $query->paginate(6);

        // ログイン済みの場合、いいね状態を追加
        if (auth()->check()) {
            $user = auth()->user();
            $recipes->getCollection()->transform(function ($recipe) use ($user) {
                $recipe->is_liked = $recipe->isLikedBy($user);
                return $recipe;
            });
        }

        return response()->json($recipes);
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

        return response()->json([
            'message' => 'レシピが投稿されました',
            'data' => $recipe
        ], 201);
    }


    public function show(Recipe $recipe)
    {
        // 公開されていないレシピはエラー
        if (!$recipe->is_published) {
            return response()->json([
                'message' => 'このレシピは公開されていません'
            ], 404);
        }

        // 閲覧数を増加
        $recipe->incrementViews();

        // リレーション読み込み
        $recipe->load(['admin', 'comments.user']);

        // ログインユーザーのいいね状態
        $user = auth()->user();
        $recipe->is_liked = $recipe->isLikedBy($user);

        // 材料と作り方を配列で返す
        $recipe->ingredients_array = $recipe->ingredients_array;
        $recipe->instructions_array = $recipe->instructions_array;

        return response()->json(['data' => $recipe]);
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

        return response()->json([
            'message' => 'レシピが更新されました',
            'data' => $recipe
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
        $query = Recipe::with('admin')->withTrashed();

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

        return response()->json($recipes);
    }

    public function adminShow(Recipe $recipe)
    {
        $recipe->load(['admin', 'comments.user', 'likes.user']);

        return response()->json(['data' => $recipe]);
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

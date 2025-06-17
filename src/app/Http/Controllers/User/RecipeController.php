<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class RecipeController extends Controller
{
    public function index()
    {
        $recipes = \App\Models\Recipe::paginate(6); // 1ページ6件など
        return view('user.index', compact('recipes'));
    }

    public function show($id)
    {
        // $recipe = Recipe::with('ingredients')->findOrFail($id);
        // return view('user.show', compact('recipe'));
        // ダミーデータ（DBなしでも表示確認）
        $recipe = (object)[
            'id' => $id,
            'title' => 'サンプルレシピ',
            'image_path' => null, // または 'recipes/sample.jpg'
            'genre' => '和食',
            'servings' => '2人分',
            'body' => "1. 材料を切る\n2. 炒める\n3. 味付けする",
            'ingredients' => collect([
                (object)['name' => 'にんじん', 'quantity' => '1本'],
                (object)['name' => '玉ねぎ', 'quantity' => '1個'],
            ])
        ];

        return view('user.show', compact('recipe'));
    }
}

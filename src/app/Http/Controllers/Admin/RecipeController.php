<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Recipe;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\RecipeRequest;

class RecipeController extends Controller
{
    
    public function index(Request $request)
    {
        $query = Recipe::query();

        // キーワード検索
        if ($request->filled('keyword')) {
            $query->where('title', 'like', '%' . $request->keyword . '%');
        }

        // ジャンルで絞り込み
        if ($request->filled('genre')) {
            $query->where('genre', $request->genre);
        }

        $recipes = $query->paginate(6); // 1ページ6件

        return view('admin.recipes.index', compact('recipes'));
    }


    public function create()
    {
        return view('admin.recipes.create');
    }


    public function store(RecipeRequest $request)
    {
        $validated = $request->validated();

        // 画像アップロード処理
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('recipes', 'public');
        }

        // レシピ保存
        Recipe::create([
            'title' => $request->title,
            'ingredients' => $request->ingredients,
            'body' => $request->body,
            'image_path' => $imagePath,
            'admin_id' => Auth::id(), // 今ログインしている管理者
        ]);

        return redirect()->route('recipes.create')->with('success', 'レシピを投稿しました！');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}

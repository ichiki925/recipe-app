@extends('layouts.app')
@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/index.css') }}">

@endsection

@section('content')
<div class="recipe-page">

<!-- 左サイドバー -->
<aside class="sidebar">
    <form method="GET" action="{{ route('admin.recipes.index') }}">
        <!-- 検索フォーム -->
        <input type="text" name="keyword" placeholder="レシピ名で検索" value="{{ request('keyword') }}">
        <button type="submit">検索</button>

        <!-- ジャンル選択 -->
        <label>ジャンルで表示</label>
        <select name="genre" onchange="this.form.submit()">
            <option value="">すべてのジャンル</option>
            <option value="和食" {{ request('genre') == '和食' ? 'selected' : '' }}>和食</option>
            <option value="洋食" {{ request('genre') == '洋食' ? 'selected' : '' }}>洋食</option>
            <option value="中華" {{ request('genre') == '中華' ? 'selected' : '' }}>中華</option>
            <!-- 必要に応じて追加 -->
        </select>
    </form>
</aside>

<!-- メイン：レシピ一覧 -->
<section class="recipe-list">
    <div class="recipe-grid">
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ2</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ3</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ4</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ5</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ6</div>
            <div class="recipe-genre">ジャンル</div>
        </div>
    </div>

    <!-- ページネーション -->
    <div class="pagination">
        {{ $recipes->links() }}
    </div>
</section>

</div>


@endsection


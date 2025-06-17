@extends('layouts.app_user')
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">

@endsection

@section('content')
<div class="recipe-page">

<!-- 左サイドバー -->
<aside class="sidebar">
    <form method="GET" action="{{ route('user.recipes.index') }}">
        <!-- 検索フォーム -->
        <div class="search-wrapper">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" name="keyword" placeholder="料理名・材料で検索" value="{{ request('keyword') }}">
        </div>
        <button type="submit">検索</button>
    </form>
</aside>

<!-- メイン：レシピ一覧 -->
<section class="recipe-list">
    <div class="recipe-grid">
        <div class="recipe-card">
            <div class="no-image">No Image</div>
            <div class="recipe-title">テストレシピ</div>
            <div class="recipe-genre">ジャンル</div>
            <div class="recipe-stats">
                ❤️ 24　　⭐ 保存済
            </div>
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


@extends('layouts.app_user')
@section('css')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="{{ asset('css/user/index.css') }}">

@endsection

@section('content')
<div class="recipe-page">

<!-- 左サイドバー -->
<aside class="sidebar">
    <h2 class="page-title">お気に入りレシピ一覧</h2>
</aside>

<!-- メイン：レシピ一覧 -->
<section class="recipe-list">
    
    <div class="recipe-grid">
    @foreach ($recipes as $recipe)
        <div class="recipe-card">
            <div class="no-image">
                @if ($recipe->image_path)
                    <img src="{{ asset('storage/' . $recipe->image_path) }}" alt="{{ $recipe->title }}">
                @else
                    No Image
                @endif
            </div>
            <div class="recipe-title">{{ $recipe->title }}</div>
            <div class="recipe-genre">{{ $recipe->genre }}</div>
            <div class="recipe-stats">
                ❤️ {{ $recipe->likes_count ?? 0 }}　
                ⭐ 保存済
            </div>
        </div>
    @endforeach
</div>

    

    <!-- ページネーション -->
    <div class="pagination">
        {{ $recipes->links() }}
    </div>
</section>

</div>


@endsection


@extends('layouts.app_user')

@section('css')
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet" />
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />




<link rel="stylesheet" href="{{ asset('css/user/show.css') }}">
@endsection


@section('content')
@php
$comments = [
    (object)['user' => (object)['name' => 'ユーザーA', 'avatar_path' => null], 'body' => 'めっちゃ美味しかったです！'],
    (object)['user' => (object)['name' => 'ユーザーB', 'avatar_path' => null], 'body' => '今度作ってみます〜'],
];
@endphp
<div class="recipe-create-container">
    <!-- 左カラム -->
    <div class="left-column">
        <h2 class="recipe-title-heading">{{ old('title') ?: 'レシピ名を入力' }}</h2>

        <div class="image-preview" id="preview" onclick="document.getElementById('imageInput').click();">
            <span id="preview-text">No Image</span>
            <img id="preview-image" src="#" alt="プレビュー" style="display: none;" />
        </div>

        <input type="file" name="image" id="imageInput" style="display: none;" accept="image/*">

        <div class="comment-section">
            <ul id="comment-list">
                @foreach (collect($comments)->reverse() as $comment)
                    <li class="comment-item">
                        @if ($comment->user->avatar_path)
                            <img src="{{ asset('storage/' . $comment->user->avatar_path) }}" class="avatar-img" alt="avatar">
                        @else
                        <span class="material-symbols-outlined avatar-icon">person</span>
                        @endif
                        <span class="username">{{ $comment->user->name }}</span>
                        <span class="comment-body">{{ $comment->body }}</span>
                    </li>
                @endforeach
            </ul>

            <div class="comment-wrapper">
                <textarea id="comment-box" class="auto-resize" placeholder="コメントを記入..."></textarea>
                <button type="submit" class="send-button" title="送信">
                    <i class="far fa-paper-plane"></i>
                </button>
            </div>

            <div class="action-buttons">
                <button class="icon-button">
                    <span class="material-symbols-outlined">favorite</span>
                    <span class="like-count">10</span>
                </button>
                <button class="icon-button">
                    <span class="material-symbols-outlined">star</span>
                    <p>お気に入り</p>
                </button>

            </div>
        </div>
    </div>

    <!-- 右カラム -->
    <div class="right-column">
        <form class="recipe-form">
            <label>ジャンル</label>
            <input type="text" name="genre" class="recipe-title" value="{{ old('genre') }}">

            <label>材料（{{ old('servings') ?: '人数' }}）</label>
            <div id="ingredients">
                <div class="ingredient-row">
                    <input type="text" name="ingredients_name[]" class="ingredient-name" placeholder="材料名">
                    <input type="text" name="ingredients_qty[]" class="ingredient-qty" placeholder="分量">
                </div>
            </div>

            <label>作り方</label>
            <textarea name="body" class="auto-resize">{{ old('body') }}</textarea>
        </form>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll('.auto-resize').forEach(function(textarea) {
        const resize = () => {
            textarea.style.height = 'auto';
            textarea.style.height = textarea.scrollHeight + 'px';
        };
        textarea.addEventListener('input', resize);
        resize();
    });
});
</script>
@endsection

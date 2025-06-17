@extends('layouts.app_admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/create.css') }}">
@endsection


@section('content')
<div class="recipe-create-container">
    <!-- プレビューエリア -->
    <div class="image-preview" id="preview" onclick="document.getElementById('imageInput').click();">
        @if ($recipe->image_path)
            <img id="preview-image" src="{{ asset('storage/' . $recipe->image_path) }}" alt="プレビュー" />
        @else
            <span id="preview-text">No Image</span>
            <img id="preview-image" src="#" alt="プレビュー" style="display: none;" />
        @endif
    </div>
    

    <!-- 非表示のファイル入力 -->
    <input type="file" name="image" id="imageInput" style="display: none;" accept="image/*">


    <form class="recipe-form" action="#" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <h2>Edit Recipe</h2>

        <label>料理名</label>
        <input type="text" name="title" class="recipe-title" value="{{ old('title', $recipe->title) }}">
        @error('title')<div class="error-message">{{ $message }}</div>@enderror

        <label>ジャンル</label>
        <input type="text" name="genre" class="recipe-title" value="{{ old('genre', $recipe->genre) }}">

        @error('genre')<div class="error-message">{{ $message }}</div>@enderror

        <label>人数</label>
        @php
            $currentServings = old('servings', $recipe->servings);
        @endphp
        <select name="servings" class="servings-input">
            <option value="">選択してください</option>
            <option value="1人分" {{ old('servings') == '1人分' ? 'selected' : '' }}>1人分</option>
            <option value="2人分" {{ old('servings') == '2人分' ? 'selected' : '' }}>2人分</option>
            <option value="3人分" {{ old('servings') == '3人分' ? 'selected' : '' }}>3人分</option>
            <option value="4人分" {{ old('servings') == '4人分' ? 'selected' : '' }}>4人分</option>
            <option value="5人以上" {{ old('servings') == '5人以上' ? 'selected' : '' }}>5人以上</option>
        </select>
        @error('servings')<div class="error-message">{{ $message }}</div>@enderror


        <label>材料</label>
        <div id="ingredients">
            @php
                $ingredients = old('ingredients_name') ? collect(old('ingredients_name'))->map(function ($name, $i) {
                    return ['name' => $name, 'qty' => old('ingredients_qty')[$i] ?? ''];
                }) : json_decode($recipe->ingredients, true);
            @endphp

            @foreach ($ingredients as $ingredient)
            <div class="ingredient-row">
                <input type="text" name="ingredients_name[]" class="ingredient-name"
                    value="{{ old('ingredients_name.$index', $ingredient['name']) }}" placeholder="材料名">
                <input type="text" name="ingredients_qty[]" class="ingredient-qty"
                    value="{{ old('ingredients_qty.$index', $ingredient['qty']) }}" placeholder="分量">
            </div>
            @endforeach
        </div>



        <label>作り方</label>
        <textarea name="body" id="body-textarea" class="auto-resize">{{ old('body', $recipe->body) }}</textarea>
        @error('body')<div class="error-message">{{ $message }}</div>@enderror



        <button type="submit">投稿する</button>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {

    // プレビュー画像処理
    document.getElementById('imageInput').addEventListener('change', function(event) {
        const file = event.target.files[0];
        const previewImage = document.getElementById('preview-image');
        const previewText = document.getElementById('preview-text');

        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                previewImage.src = e.target.result;
                previewImage.style.display = 'block';
                if (previewText) previewText.style.display = 'none';
            };
            reader.readAsDataURL(file);
        }
    });

    // 材料入力欄の自動追加
    const container = document.getElementById('ingredients');
    container.addEventListener('input', (e) => {
        const lastRow = container.querySelector('.ingredient-row:last-child');
        const name = lastRow.querySelector('input[name="ingredients_name[]"]').value;
        const qty = lastRow.querySelector('input[name="ingredients_qty[]"]').value;

        if (name !== '' || qty !== '') {
            const newRow = lastRow.cloneNode(true);
            newRow.querySelectorAll('input').forEach(input => input.value = '');
            container.appendChild(newRow);
        }
    });

    // 作り方textareaの自動リサイズ（show.bladeと同じ安定版）
    document.querySelectorAll('.auto-resize').forEach(function(textarea) {
        const resize = () => {
            textarea.style.height = 'auto';
            const height = textarea.scrollHeight;
            textarea.style.height = (height > 80 ? height : 80) + 'px'; // 最低80pxに
        };
        textarea.addEventListener('input', resize);
        resize(); // 初期化
    });
});
</script>
@endsection


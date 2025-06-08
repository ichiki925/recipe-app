@extends('layouts.app')

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

        <label>材料</label>
        <input type="text" name="servings" class="servings-input"
        value="{{ old('servings', $recipe->servings ?? '') }}" placeholder="例：2人分">
        <div id="ingredients">
            @php
                $ingredients = json_decode($recipe->ingredients, true) ?? [['name' => '', 'qty' => '']];
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
        <textarea name="body">{{ old('body', $recipe->body) }}</textarea>
        @error('body')<div class="error-message">{{ $message }}</div>@enderror



        <button type="submit">投稿する</button>
    </form>
</div>
@endsection

@section('scripts')
<script>
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



function setupAutoAdd() {
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
}
document.addEventListener('DOMContentLoaded', setupAutoAdd);
</script>
@endsection

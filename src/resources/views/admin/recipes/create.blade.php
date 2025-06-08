@extends('layouts.app')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/create.css') }}">
@endsection


@section('content')
<div class="recipe-create-container">
    <!-- プレビューエリア -->
    <div class="image-preview" id="preview" onclick="document.getElementById('imageInput').click();">
        <span id="preview-text">No Image</span>
        <img id="preview-image" src="#" alt="プレビュー" style="display: none;" />
    </div>

    <!-- 非表示のファイル入力 -->
    <input type="file" name="image" id="imageInput" style="display: none;" accept="image/*">


    <form class="recipe-form" action="#" method="POST" enctype="multipart/form-data">
        @csrf

        <h2>New Recipe</h2>

        <label>料理名</label>
        <input type="text" name="title" class="recipe-title" value="{{ old('title') }}">
        @error('title')<div class="error-message">{{ $message }}</div>@enderror

        <label>材料</label>
        <input type="text" name="servings" class="servings-input" value="{{ old('servings') }}" placeholder="例：2人分">
        <div id="ingredients">
            <div class="ingredient-row">
                <input type="text" name="ingredients_name[]" class="ingredient-name" placeholder="材料名">
                <input type="text" name="ingredients_qty[]" class="ingredient-qty" placeholder="分量">
            </div>
        </div>

        <label>作り方</label>
        <textarea name="body">{{ old('body') }}</textarea>
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
            previewText.style.display = 'none';
        };
        reader.readAsDataURL(file);
    }
});

document.getElementById('addRow').addEventListener('click', () => {
    const row = document.querySelector('.ingredient-row').cloneNode(true);
    // 中身を空にして追加
    row.querySelectorAll('input').forEach(i => i.value = '');
    document.getElementById('ingredients').appendChild(row);
    });

    // 行削除
    document.addEventListener('click', e => {
    if (e.target.classList.contains('remove-row')) {
        const rows = document.querySelectorAll('.ingredient-row');
        if (rows.length > 1) e.target.parentElement.remove();
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

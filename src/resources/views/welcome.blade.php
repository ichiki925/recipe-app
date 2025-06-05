<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanilla's Kitchen</title>
    <link rel="stylesheet" href="{{ asset('css/welcome.css') }}">
</head>
<body>
    <div class="container">
        <img src="{{ asset('images/rabbit-shape.svg') }}" alt="ロゴ" class="logo">
        <h1 class="title">Vanilla's Kitchen</h1>
        <p class="subtitle">日々のお料理レシピを記録</p>
        <div class="buttons">
            <a href="{{ route('register') }}" class="btn">新規登録</a>
            <a href="{{ route('login') }}" class="btn ghost">ログイン</a>
        </div>
    </div>
</body>
</html>

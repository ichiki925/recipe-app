<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ログイン</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/auth/login.css') }}" />

</head>
<body>
    <header class="header">
        <div class="container">
            <div class="logo">
                <img src="{{ asset('images/rabbit-shape.svg') }}" alt="Rabbit Logo" class="logo-image">
            </div>
        </div>
    </header>
    <div class="form-container">
        <form class="login-form" action="/login" method="post">
            @csrf
            <h1 class="login-title">Login</h1>
            <div class="form-group">
                <label class="form-label">メールアドレス</label>
                <input type="text" class="form-input" name="email" value="{{ old('email') }}">
                @error('email')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <div class="form-group">
                <label class="form-label">パスワード</label>
                <input type="password" class="form-input" name="password">
                @error('password')
                    <div class="error">{{ $message }}</div>
                @enderror
            </div>
            <button type="submit" class="submit-button">ログイン</button>
            <div class="form-footer">
                <a href="{{ route('password.request') }}">パスワードを忘れた方はこちら</a>
            </div>
            <div class="form-footer">
                <a href="{{ route('register') }}">アカウントをお持ちでない方はこちら</a>
            </div>
            <div class="form-footer">
                <a href="{{ url('/') }}">トップページに戻る</a>
            </div>
        </form>
    </div>
</body>
</html>
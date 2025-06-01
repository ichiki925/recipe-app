<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>パスワード再設定</title>
    <link rel="stylesheet" href="{{ asset('css/auth/reset-password.css') }}">
</head>
<body>
<div class="form-container">
    <h1 class="login-title">新しいパスワードの設定</h1>

    <form method="POST" action="{{ route('password.update') }}">
        @csrf

        <input type="hidden" name="token" value="{{ $token }}">

        <div class="form-group">
            <label class="form-label">メールアドレス</label>
            <input type="email" name="email" class="form-input" value="{{ old('email') }}" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">新しいパスワード</label>
            <input type="password" name="password" class="form-input" required>
            @error('password')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label">パスワード確認</label>
            <input type="password" name="password_confirmation" class="form-input" required>
        </div>

        <button type="submit" class="submit-button">パスワードを変更</button>
    </form>
    <a href="{{ route('login') }}" class="login-link">ログイン画面に戻る</a>
</div>
</body>
</html>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vanilla's Kitchen</title>
    <link rel="stylesheet" href="{{ asset('css/auth/forgot-password.css') }}">
</head>
<body>
<div class="form-container">
    <h1 class="title">パスワードをお忘れですか？</h1>
    <p class="description">
        アカウントにアクセスするには、
        登録したメールアドレスを入力してください。
    </p>

    @if (session('status'))
        <div class="success-message">
        パスワード再設定用のメールを送信しました。<br>
        ご確認ください。
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}">
        @csrf
        <div class="form-group">
            <label class="form-label">メールアドレス</label>
            <input type="email" name="email" class="form-input" required>
            @error('email')
                <div class="error">{{ $message }}</div>
            @enderror
        </div>
        <button type="submit" class="submit-button">再設定リンクを送信</button>
    </form>
    <a href="{{ route('login') }}" class="login-link">ログイン画面に戻る</a>
</div>
</body>

</html>


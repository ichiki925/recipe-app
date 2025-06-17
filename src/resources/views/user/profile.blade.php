@extends('layouts.app_user')
@section('css')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined&display=swap" />
<link rel="stylesheet" href="{{ asset('css/user/profile.css') }}">

@endsection

@section('content')

<div class="profile-container">
    <h2>プロフィール編集</h2>

    <!-- アイコン -->
    <div class="avatar-section">
        @if ($user->avatar)
            <img src="{{ asset('storage/' . $user->avatar) }}" alt="アイコン" class="avatar-img">
        @else
        <div class="avatar-icon">
            <span class="material-symbols-outlined">person</span>
        </div>
        @endif
        <input type="file" name="avatar">
    </div>

    <!-- 名前 -->
    <label>ユーザーネーム</label>
    <input type="text" name="name" value="{{ old('name', $user->name) }}">

    

    <!-- 保存ボタン -->
    <button type="submit">保存する</button>
</div>
@endsection
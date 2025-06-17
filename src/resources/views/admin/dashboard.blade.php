@extends('layouts.app_admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/dashboard.css') }}">
@endsection

@section('content')
<div class="dashboard-container">
    <h1>管理者ダッシュボード</h1>

    <div class="dashboard-stats">
        <div class="dashboard-card">
            <span>全レシピ数</span>
            <strong>{{ $totalRecipes }} 件</strong>
        </div>
        <div class="dashboard-card">
            <span>最近更新されたレシピ</span>
            <strong>{{ $totalRecipes }} 件</strong>
        </div>
        <div class="dashboard-card">
            <span>ユーザー登録数</span>
            <strong>{{ $todayRecipes }} 件</strong>
        </div>
    </div>

    <div class="recent-deleted">
        <h2>🗑 最近削除されたレシピ</h2>
        <ul class="deleted-list">
            @foreach ($deletedRecipes as $recipe)
                <li>
                    {{ $recipe->title }}
                    <a href="{{ route('admin.recipes.edit', $recipe->id) }}">編集</a>
                </li>
            @endforeach
        </ul>
    </div>
</div>
@endsection

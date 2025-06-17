@extends('layouts.app_admin')

@section('css')
<link rel="stylesheet" href="{{ asset('css/admin/comments/index.css') }}">
@endsection

@section('content')
<h1>Comments</h1>

<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>ユーザー名</th>
            <th>レシピ</th>
            <th>コメント</th>
            <th>投稿日</th>
            <th>操作</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($comments as $comment)
        <tr>
            <td>{{ $comment->id }}</td>
            <td>{{ $comment->user->name }}</td>
            <td>{{ $comment->recipe->title }}</td>
            <td>{{ $comment->body }}</td>
            <td>{{ $comment->created_at->format('Y-m-d') }}</td>
            <td>
                <form method="POST" action="#">
                    @csrf
                    @method('DELETE')
                    <button>削除</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endsection

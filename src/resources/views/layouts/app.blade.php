<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理者</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <img src="{{ asset('images/rabbit-shape-colored.svg') }}" class="logo-image" alt="Rabbit Logo">
                <span class="logo-text">Vanilla's Kitchen</span>
            </div>
            <nav>
                @if (Auth::check() && Auth::user()->role === 'admin')
                <ul>
                    <li><a href="#">Recipe List</a></li>
                    <li><a href="#">New Recipe</a></li>
                    <li>
                        <form id="logout-form" action="{{ url('/admin/logout') }}" method="POST" style="display: inline;">
                            @csrf
                            <button type="submit" class="logout-button">logout</button>
                        </form>
                    </li>
                </ul>
                @endif
            </nav>
        </div>
    </header>

    <main>
        @yield('content')
    </main>
    @yield('scripts')
</body>
</html>

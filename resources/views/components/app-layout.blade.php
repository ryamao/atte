@props(['css'])

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Atte</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}" />
    @isset($css)
    <link rel="stylesheet" href="{{ asset('css/' . $css) }}" />
    @endisset
</head>

<body>
    <header class="header">
        <h1 class="header__title">Atte</h1>

        @if (Auth::check())
        <nav>
            <ul>
                <li><a href="{{ route('stamp') }}">ホーム</a></li>
                <li><a href="{{ route('attendance') }}">日付一覧</a></li>
                <li>
                    <form action="{{ route('logout') }}" method="post">
                        @csrf
                        <button type="submit" dusk="logout">ログアウト</button>
                    </form>
                </li>
            </ul>
        </nav>
        @endif
    </header>
    <main class="content">
        {{ $slot }}
    </main>
    <footer class="footer">
        <small class="footer__text">Atte, inc.</small>
    </footer>
</body>

</html>
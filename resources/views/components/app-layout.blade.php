@props(['css'])

<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Atte</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+JP:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/reset.css') }}" />
    <link rel="stylesheet" href="{{ asset('css/app-layout.css') }}" />
    @isset($css)
    <link rel="stylesheet" href="{{ asset('css/' . $css) }}" />
    @endisset
</head>

<body>
    <header class="header">
        <h1 class="header__title">Atte</h1>

        @if (Auth::check())
        <nav class="header__nav">
            <ul class="header__nav-list">
                @if (Auth::user()->hasVerifiedEmail())
                <li class="header__nav-item"><a href="{{ route('stamp') }}">ホーム</a></li>
                <li class="header__nav-item"><a href="{{ route('attendance') }}">日付一覧</a></li>
                @endif
                <li class="header__nav-item">
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
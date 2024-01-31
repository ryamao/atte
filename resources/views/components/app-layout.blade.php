<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atte</title>
</head>

<body>
    <header>
        <h1>Atte</h1>

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
    <main>
        {{ $slot }}
    </main>
    <footer>
        <small>Atte, inc.</small>
    </footer>
</body>

</html>
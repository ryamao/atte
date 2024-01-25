@props(['subtitle'])

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
                <li><a href="/">ホーム</a></li>
                <li><a href="/attendance">日付一覧</a></li>
                <li><button dusk="logout">ログアウト</button></li>
            </ul>
        </nav>
        @endif
    </header>
    <main>
        @isset($subtitle)
        <h2>{{ $subtitle }}</h2>
        @endisset

        {{ $slot }}
    </main>
    <footer>
        <small>Atte, inc.</small>
    </footer>
</body>

</html>
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
    </header>
    <main>
        <h2>{{ $subtitle }}</h2>
        {{ $slot }}
    </main>
    <footer>
        <small>Atte, inc.</small>
    </footer>
</body>

</html>
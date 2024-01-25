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
        <h2>ログイン</h2>
        <form action="/login" method="post" novalidate>
            @csrf
            <input type="email" name="email" placeholder="メールアドレス" />
            @error('email')
            <div dusk="email-alert">{{ $message }}</div>
            @enderror
            <input type="password" name="password" placeholder="パスワード" />
            @error('password')
            <div dusk="password-alert">{{ $message }}</div>
            @enderror
            <button type="submit">ログイン</button>
            <a href="/register">会員登録</a>
        </form>
    </main>
    <footer>
        <small>Atte, inc.</small>
    </footer>
</body>

</html>
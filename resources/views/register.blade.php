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
        <h2>会員登録</h2>
        <form action="/register" method="post" novalidate>
            @csrf
            <input type="text" name="name" placeholder="名前" />
            @error('name')
            <div dusk="name-alert">{{ $message }}</div>
            @enderror
            <input type="email" name="email" placeholder="メールアドレス" />
            @error('email')
            <div dusk="email-alert">{{ $message }}</div>
            @enderror
            <input type="password" name="password" placeholder="パスワード" />
            @error('password')
            <div dusk="password-alert">{{ $message }}</div>
            @enderror
            <input type="password" name="password_confirmation" placeholder="確認用パスワード" />
            <button type="submit">会員登録</button>
            <a href="/login">ログイン</a>
        </form>
    </main>
    <footer>
        <small>Atte, inc.</small>
    </footer>
</body>

</html>
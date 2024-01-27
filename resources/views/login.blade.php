<x-app-layout>
    <h2>ログイン</h2>

    <form action="{{ route('login') }}" method="post" novalidate>
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
        <p dusk="info-text">アカウントをお持ちでない方はこちらから</p>
        <a href="{{ route('register') }}">会員登録</a>
    </form>
</x-app-layout>
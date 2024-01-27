<x-app-layout>
    <h2>会員登録</h2>

    <form action="{{ route('register') }}" method="post" novalidate>
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
        <p dusk="info-text">アカウントをお持ちの方はこちらから</p>
        <a href="{{ route('login') }}">ログイン</a>
    </form>
</x-app-layout>
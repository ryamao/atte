<x-app-layout>
    <x-slot name="subtitle">ログイン</x-slot>

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
</x-app-layout>
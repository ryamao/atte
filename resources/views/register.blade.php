<x-app-layout>
    <x-slot name="subtitle">会員登録</x-slot>

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
</x-app-layout>
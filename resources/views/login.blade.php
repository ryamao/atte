<x-app-layout>
    <x-slot name="css">login.css</x-slot>

    <div class="login">
        <h2 class="login__title">ログイン</h2>

        <form class="login__form" action="{{ route('login') }}" method="post" novalidate>
            @csrf
            <div class="login__form-field">
                <input type="email" name="email" placeholder="メールアドレス" />
                @error('email')
                <div class="login__form-alert" dusk="email-alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="login__form-field">
                <input type="password" name="password" placeholder="パスワード" />
                @error('password')
                <div class="login__form-alert" dusk="password-alert">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">ログイン</button>
        </form>

        <p class="login__info" dusk="info-text">アカウントをお持ちでない方はこちらから</p>
        <a class="login__register-link" href="{{ route('register') }}">会員登録</a>
    </div>
</x-app-layout>
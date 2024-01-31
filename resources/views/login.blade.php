<x-app-layout>
    <x-slot name="css">auth.css</x-slot>

    <div class="auth">
        <h2 class="auth__title">ログイン</h2>

        <form class="auth__form" action="{{ route('login') }}" method="post" novalidate>
            @csrf
            <div class="auth__form-field">
                <input type="email" name="email" placeholder="メールアドレス" />
                @error('email')
                <div class="auth__form-alert" dusk="email-alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__form-field">
                <input type="password" name="password" placeholder="パスワード" />
                @error('password')
                <div class="auth__form-alert" dusk="password-alert">{{ $message }}</div>
                @enderror
            </div>

            <button type="submit">ログイン</button>
        </form>

        <p class="auth__info" dusk="info-text">アカウントをお持ちでない方はこちらから</p>
        <a class="auth__link" href="{{ route('register') }}">会員登録</a>
    </div>
</x-app-layout>
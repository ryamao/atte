<x-app-layout>
    <x-slot name="css">auth.css</x-slot>

    <div class="auth">
        <h2 class="auth__title">会員登録</h2>

        <form class="auth__form" action="{{ route('register') }}" method="post" novalidate>
            @csrf
            <div class="auth__form-field">
                <input type="text" name="name" placeholder="名前" value="{{ old('name') }}" />
                @error('name')
                <div class="auth__form-alert" dusk="name-alert">{{ $message }}</div>
                @enderror
            </div>

            <div class="auth__form-field">
                <input type="email" name="email" placeholder="メールアドレス" value="{{ old('email') }}" />
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

            <div class="auth__form-field">
                <input type="password" name="password_confirmation" placeholder="確認用パスワード" />
            </div>

            <button type="submit">会員登録</button>
        </form>

        <p class="auth__info" dusk="info-text">アカウントをお持ちの方はこちらから</p>
        <a class="auth__link" href="{{ route('login') }}">ログイン</a>
    </div>
</x-app-layout>
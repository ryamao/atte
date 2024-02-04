<x-app-layout>
    <x-slot name="css">verify-email.css</x-slot>

    <div class="verify">
        <h2 class="verify__title">メール認証</h2>

        <div class="verify__layout">
            <p class="verify__text">
                ご登録ありがとうございます！<br />
                ご入力いただいたメールアドレスへ認証リンクを送信いたしました。
                認証リンクをクリックしてメールアドレスの認証を完了させてください。
                認証メールが届かない場合は再送信させていただきます。
            </p>

            <form class="verify__form" method="POST" action="{{ route('verification.send') }}">
                @csrf

                <button type="submit">メールを再送信する</button>

                @if (session('status') === 'verification-link-sent')
                    <span class="verify__notice">認証メールを送信しました</span>
                @endif
            </form>
        </div>
    </div>
</x-app-layout>

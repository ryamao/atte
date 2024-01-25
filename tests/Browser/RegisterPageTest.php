<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class RegisterPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = ['users', 'sessions'];

    /**
     * @testdox [会員登録ページ] [テキスト] "$selector" - "$expected"
     * @group register
     * @testWith ["header h1", "Atte"]
     *           ["main h2", "会員登録"]
     *           ["@info-text", "アカウントをお持ちの方はこちらから"]
     *           ["footer small", "Atte, inc."]
     */
    public function test_register_page_has_text(string $selector, string $expected): void
    {
        $this->browse(function (Browser $browser) use ($selector, $expected) {
            $browser->visit('/register');
            $text = $browser->text($selector);
            $this->assertEquals($expected, $text);
        });
    }

    /**
     * @testdox [会員登録ページ] [入力フィールド] [$field] タイプしたテキストが入力されている
     * @group register
     * @testWith ["name"]
     *           ["email"]
     *           ["password"]
     *           ["password_confirmation"]
     */
    public function test_register_page_holds_typed_text_in_input_field(string $field): void
    {
        $this->browse(function (Browser $browser) use ($field) {
            $value = $field . '_test';
            $browser->visit('/register');
            $browser->assertInputValue($field, '');
            $browser->type($field, $value);
            $browser->assertInputValue($field, $value);
        });
    }

    /**
     * @testdox [会員登録ページ] [入力フィールド] [$field] プレースホルダが表示されている
     * @group register
     * @testWith ["name", "名前"]
     *           ["email", "メールアドレス"]
     *           ["password", "パスワード"]
     *           ["password_confirmation", "確認用パスワード"]
     */
    public function test_register_page_has_placeholder_in_input_field(string $field, string $placeholder): void
    {
        $this->browse(function (Browser $browser) use ($field, $placeholder) {
            $browser->visit('/register');
            $browser->assertAttribute("input[name=\"{$field}\"", 'placeholder', $placeholder);
        });
    }

    /**
     * @testdox [会員登録ページ] [ボタン/リンク] [会員登録] 会員登録ボタンが表示されている
     * @group register
     */
    public function test_register_page_displays_submit_button(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->assertSeeIn('button[type="submit"]', '会員登録');
        });
    }

    /**
     * @testdox [会員登録ページ] [ボタン/リンク] [会員登録] バリデーションエラー時に会員登録ページに戻される
     * @group register
     */
    public function test_register_page_can_redirect_to_register_page_if_submit_button_is_pressed_and_validation_fails(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->press('会員登録');
            $browser->assertPathIs('/register');
        });
    }

    /**
     * @testdox [会員登録ページ] [ボタン/リンク] [会員登録] 会員登録後、打刻ページに遷移する
     * @group register
     */
    public function test_register_page_can_redirect_to_stamping_page_if_submit_button_is_pressed_and_validation_succeeds(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->type('name', 'a');
            $browser->type('email', 'test@example.com');
            $browser->type('password', 'password');
            $browser->type('password_confirmation', 'password');
            $browser->press('会員登録');
            $browser->assertPathIs('/');
        });
    }

    /**
     * @testdox [会員登録ページ] [ボタン/リンク] [ログイン] ログインリンクが表示されている
     * @group register
     */
    public function test_register_page_has_link_named_login(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->assertSeeLink('ログイン');
        });
    }

    /**
     * @testdox [会員登録ページ] [ボタン/リンク] [ログイン] ログインリンクをクリックするとログインページに遷移する
     * @group register
     */
    public function test_register_page_links_to_login_page(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->clickLink('ログイン');
            $browser->assertPathIs('/login');
        });
    }

    /**
     * @testdox [会員登録ページ] [入力フィールド]
     * @group register
     * @dataProvider provideInputFieldTestData
     */
    public function test_register_page_displays_error_message_or_not(string $field, string $value, ?string $alert): void
    {
        $this->browse(function (Browser $browser) use ($field, $value, $alert) {
            $selector = "@{$field}-alert";

            $browser->visit('/register');

            $browser->type($field, $value);
            if ($field === 'password') {
                $browser->type('password_confirmation', $value);
            } else if ($field === 'password_confirmation') {
                $selector = '@password-alert';
                $browser->type('password', 'password');
            }

            $browser->press('会員登録');

            if (is_null($alert)) {
                $this->assertThrows(
                    fn () => $browser->text($selector),
                    \Facebook\WebDriver\Exception\NoSuchElementException::class,
                );
            } else {
                $browser->assertSeeIn($selector, $alert);
            }
        });
    }

    /** 会員登録ページの入力フィールドのバリデーション用テストデータ */
    public static function provideInputFieldTestData(): array
    {
        return [
            '"name" - 1文字 - ok' => ['name', 'a', null],
            '"name" - 191文字 - ok' => ['name', str_repeat('a', 191), null],
            '"name" - 未入力 - エラー' => ['name', '', '名前を入力してください'],
            '"name" - 192文字 - エラー' => ['name', str_repeat('a', 192), '名前は191文字以内で入力してください'],
            '"email" - メール形式 - ok' => ['email', 'test@example.com', null],
            '"email" - ローカル64文字、ドメイン63文字 - ok' => ['email', str_repeat('a', 64) . '@' . str_repeat('a', 63), null],
            '"email" - 未入力 - エラー' => ['email', '', 'メールアドレスを入力してください'],
            '"email" - 非メール形式 - エラー' => ['email', 'example.com', '有効なメールアドレスを入力してください'],
            '"email" - ローカル64文字、ドメイン64文字 - エラー' => ['email', str_repeat('a', 64) . '@' . str_repeat('a', 64), '有効なメールアドレスを入力してください'],
            '"password" - 8文字 - ok' => ['password', str_repeat('a', 8), null],
            '"password" - 191文字 - ok' => ['password', str_repeat('a', 191), null],
            '"password" - 未入力 - エラー' => ['password', '', 'パスワードを入力してください'],
            '"password" - 7文字 - エラー' => ['password', str_repeat('a', 7), 'パスワードは8文字以上で入力してください'],
            '"password" - 192文字 - エラー' => ['password', str_repeat('a', 192), 'パスワードは191文字以内で入力してください'],
            '"password_confirmation" - 不一致 - エラー' => ['password_confirmation', 'password2', '確認用パスワードと一致しません'],
            '"password_confirmation" - 未入力 - エラー' => ['password_confirmation', '', '確認用パスワードと一致しません'],
        ];
    }

    /**
     * @testdox [会員登録ページ] [入力フィールド] [email] メールアドレスが登録済みの場合、エラーメッセージが表示される
     * @group register
     */
    public function test_register_page_will_display_error_message_for_email_if_same_email_is_registered(): void
    {
        User::create([
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => Hash::make('password1'),
        ]);

        $this->browse(function (Browser $browser) {
            $browser->visit('/register');
            $browser->type('name', 'b');
            $browser->type('email', 'test@example.com');
            $browser->type('password', 'password2');
            $browser->type('password_confirmation', 'password2');
            $browser->press('会員登録');
            $browser->assertSeeIn('@email-alert', '同じメールアドレスが既に登録されています');
        });
    }
}

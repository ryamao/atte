<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class LoginPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = ['users', 'sessions'];

    private Collection $userData;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        RateLimiter::for('login', function ($job) {
            return Limit::none();
        });

        $this->userData = collect([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $hashedPassword = Hash::make($this->userData['password']);
        User::create($this->userData->merge(['password' => $hashedPassword])->all());
    }

    /**
     * @testdox [ログインページ] [テキスト] "$selector" - "$expected"
     * @group login
     * @testWith ["header h1", "Atte"]
     *           ["main h2", "ログイン"]
     *           ["@info-text", "アカウントをお持ちでない方はこちらから"]
     *           ["footer small", "Atte, inc."]
     */
    public function testLoginPageHasText(string $selector, string $expected): void
    {
        $this->browse(function (Browser $browser) use ($selector, $expected) {
            $browser->visitRoute('login');
            $text = $browser->text($selector);
            $this->assertEquals($expected, $text);
        });
    }

    /**
     * @testdox [ログインページ] [入力フィールド] [$field] タイプしたテキストが入力されている
     * @group login
     * @testWith ["email"]
     *           ["password"]
     */
    public function testLoginPageHasInputField(string $field): void
    {
        $this->browse(function (Browser $browser) use ($field) {
            $value = $field . '_test';
            $browser->visitRoute('login');
            $browser->assertInputValue($field, '');
            $browser->type($field, $value);
            $browser->assertInputValue($field, $value);
        });
    }

    /**
     * @testdox [ログインページ] [入力フィールド] [$field] プレースホルダが表示されている
     * @group login
     * @testWith ["email", "メールアドレス"]
     *           ["password", "パスワード"]
     */
    public function testLoginPageHasInputFieldPlaceholder(string $field, string $placeholder): void
    {
        $this->browse(function (Browser $browser) use ($field, $placeholder) {
            $browser->visitRoute('login');
            $browser->assertAttribute("input[name=\"{$field}\"", 'placeholder', $placeholder);
        });
    }

    /**
     * @testdox [ログインページ] [ボタン/リンク] [ログイン] ログインボタンが表示されている
     * @group login
     */
    public function testLoginPageHasSubmitButtonNamedLogin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');
            $browser->assertSeeIn('button[type="submit"]', 'ログイン');
        });
    }

    /**
     * @testdox [ログインページ] [ボタン/リンク] [ログイン] バリデーションエラー時にログインページに戻される
     * @group login
     */
    public function testLoginPageRedirectsToLoginPageIfValidationFails(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');
            $browser->press('ログイン');
            $browser->assertRouteIs('login');
        });
    }

    /**
     * @testdox [ログインページ] [ボタン/リンク] [ログイン] バリデーション成功時に打刻ページに遷移する
     * @group login
     */
    public function testLoginPageRedirectsToStampPageIfValidationSucceeds(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');
            foreach (['email', 'password'] as $field) {
                $browser->type($field, $this->userData[$field]);
            }
            $browser->press('ログイン');
            $browser->assertRouteIs('stamp');
        });
    }

    /**
     * @testdox [ログインページ] [ボタン/リンク] [会員登録] 会員登録ページへのリンクが表示されている
     * @group login
     */
    public function testLoginPageHasLinkToRegisterPage(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');
            $browser->assertSeeLink('会員登録');
        });
    }

    /**
     * @testdox [ログインページ] [ボタン/リンク] [会員登録] 会員登録ページへのリンクをクリックすると会員登録ページに遷移する
     * @group login
     */
    public function testLoginPageRedirectsToRegisterPageIfLinkIsClicked(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->visitRoute('login');
            $browser->clickLink('会員登録');
            $browser->assertRouteIs('register');
        });
    }

    /**
     * @testdox [ログインページ] [入力フィールド] バリデーション
     * @group login
     * @dataProvider provideInputFieldTestData
     */
    public function testLoginPageCanValidateInputField(string $field, string $value, ?string $alert): void
    {
        $callback = function (Browser $browser) use ($field, $value, $alert) {
            $browser->visitRoute('login');
            $browser->type($field, $value);
            $browser->press('ログイン');

            $selector = "@{$field}-alert";
            if (is_null($alert)) {
                $this->assertThrows(
                    fn () => $browser->text($selector),
                    \Facebook\WebDriver\Exception\NoSuchElementException::class,
                );
            } else {
                $browser->assertSeeIn($selector, $alert);
            }
        };

        /* FIXME ログイン回数制限を一時的に無効化する */
        retry(
            times: 12,
            callback: fn () => $this->browse($callback),
            sleepMilliseconds: 10 * 1000,
            when: fn (\Exception $exception) => $exception instanceof \Facebook\WebDriver\Exception\NoSuchElementException
        );
    }

    /** ログインページの入力フィールドのバリデーション用テストデータ */
    public static function provideInputFieldTestData(): array
    {
        return [
            '"email" - 登録済み - ok' => ['email', 'test@example.com', null],
            '"email" - 未登録 - ok' => ['email', 'test2@example.com', null],
            '"email" - 未入力 - エラー' => ['email', '', 'メールアドレスを入力してください'],
            '"password" - 入力 - ok' => ['password', 'password', null],
            '"password" - 未入力 - エラー' => ['password', '', 'パスワードを入力してください'],
        ];
    }

    /**
     * @testdox [ログインページ] [入力フィールド] [認証失敗] email="$email" - password="$password"
     * @group login
     * @testWith ["test2@example.com", "password"]
     *           ["test@example.com", "password2"]
     */
    public function testLoginPageCanValidateAuthenticationFailure(string $email, string $password): void
    {
        $callback = function (Browser $browser) use ($email, $password) {
            $browser->visitRoute('login');
            $browser->type('email', $email);
            $browser->type('password', $password);
            $browser->press('ログイン');
            $browser->assertSeeIn('@email-alert', '会員情報が登録されていません');
        };

        /* FIXME ログイン回数制限を一時的に無効化する */
        retry(
            times: 12,
            callback: fn () => $this->browse($callback),
            sleepMilliseconds: 10 * 1000,
            when: fn (\Exception $exception) => $exception instanceof \Facebook\WebDriver\Exception\NoSuchElementException
        );
    }
}

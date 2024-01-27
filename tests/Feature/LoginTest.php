<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    protected Collection $userData;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->userData = collect([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $hashedPassword = Hash::make($this->userData['password']);
        User::create($this->userData->merge(['password' => $hashedPassword])->all());
    }

    /**
     * @testdox [GET login] ステータスコード200を返す
     * @group login
     */
    public function test_get_login_returns_status_code_200(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST login] [認証成功] 認証状態になる
     * @group login
     */
    public function test_post_login_with_valid_parameters_authenticates_current_user(): void
    {
        $user = User::where('email', $this->userData['email'])->first();
        $this->fromRoute('login')->post(route('login'), $this->userData->only(['email', 'password'])->all());
        $this->assertAuthenticatedAs($user);
    }

    /**
     * @testdox [POST login] [認証成功] route('stamp') へリダイレクトする
     * @group login
     */
    public function test_post_login_with_valid_parameters_redirects_to_stamping_page(): void
    {
        $response = $this->fromRoute('login')->post(route('login'), $this->userData->only(['email', 'password'])->all());
        $response->assertRedirectToRoute('stamp');
    }

    /**
     * @testdox [POST login] [認証失敗] 非認証状態を維持する
     * @group login
     */
    public function test_post_login_with_no_parameters_doesnt_authenticates_current_user(): void
    {
        $this->fromRoute('login')->post(route('login'));
        $this->assertGuest();
    }

    /**
     * @testdox [POST login] [認証失敗] route('login') へリダイレクトする
     * @group login
     */
    public function test_post_login_with_no_parameters_redirects_to_login_page(): void
    {
        $response = $this->fromRoute('login')->post(route('login'));
        $response->assertRedirectToRoute('login');
    }

    /**
     * @testdox [POST login] [バリデーション]
     * @group login
     * @dataProvider provideLoginData
     */
    public function test_post_login_with_various_parameters_causes_validation_error_or_not(string $field, mixed $value, ?string $alert): void
    {
        $response = $this->fromRoute('login')->post(route('login'), [$field => $value]);
        if (is_null($alert)) {
            $response->assertValid($field);
        } else {
            $response->assertInvalid([$field => $alert]);
        }
    }

    public static function provideLoginData(): array
    {
        return [
            '"email" - ローカル1文字、ドメイン1文字 - OK' => ['email', 'a@a', null],
            '"email" - ローカル64文字、ドメイン63文字 - OK' => ['email', str_repeat('a', 64) . '@' . str_repeat('a', 63), null],
            '"email" - ローカル64文字、ドメイン64文字 - OK' => ['email', str_repeat('a', 64) . '@' . str_repeat('a', 64), null],
            '"email" - 非メール形式 - OK' => ['email', 'example.com', null],
            '"email" - null - エラー' => ['email', null, 'メールアドレスを入力してください'],
            '"email" - 非文字列 - エラー' => ['email', 123, 'メールアドレスは文字列を入力してください'],
            '"password" - 8文字 - OK' => ['password', str_repeat('a', 8), null],
            '"password" - 191文字 - OK' => ['password', str_repeat('a', 191), null],
            '"password" - 7文字 - OK' => ['password', str_repeat('a', 7), null],
            '"password" - 192文字 - OK' => ['password', str_repeat('a', 192), null],
            '"password" - null - エラー' => ['password', null, 'パスワードを入力してください'],
            '"password" - 非文字列 - エラー' => ['password', 123, 'パスワードは文字列を入力してください'],
        ];
    }

    /**
     * @testdox [POST login] [バリデーション] "email" が未登録の場合、エラーメッセージが返る
     * @group login
     */
    public function test_post_login_with_unregistered_email_causes_validation_error_for_email(): void
    {
        $data = $this->userData->only('password')->merge(['email' => 'test2@example.com'])->all();
        $response = $this->fromRoute('login')->post(route('login'), $data);
        $response->assertInvalid(['email' => '会員情報が登録されていません']);
    }
}

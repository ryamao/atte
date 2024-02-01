<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox [GET register] ステータスコード200を返す
     *
     * @group register
     */
    public function testGetRegisterReturnsStatusCode200(): void
    {
        $response = $this->get(route('register'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST register] [登録成功] route('stamp') にリダイレクトする
     *
     * @group register
     */
    public function testPostRegisterRedirectsToStampPageIfRegistrationSucceeds(): void
    {
        $response = $this->fromRoute('register')->post(route('register'), [
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertRedirectToRoute('stamp');
    }

    /**
     * @testdox [POST register] [登録成功] バリデーションに成功する
     *
     * @group register
     */
    public function testPostRegisterValidatesSuccessfullyIfRegistrationSucceeds(): void
    {
        $response = $this->fromRoute('register')->post(route('register'), [
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);
        $response->assertValid();
    }

    /**
     * @testdox [POST register] [登録成功] usersテーブルにリクエストパラメータを保存する
     *
     * @group register
     */
    public function testPostRegisterSavesRequestParametersToUsersTableIfRegistrationSucceeds(): void
    {
        $this->assertDatabaseEmpty('users');

        $this->fromRoute('register')->post(route('register'), [
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertDatabaseCount('users', 1);
        $user = User::first();
        $this->assertSame('a', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertTrue(Hash::check('password', $user->password));
    }

    /**
     * @testdox [POST register] [登録成功] 認証状態になる
     *
     * @group register
     */
    public function testPostRegisterAuthenticatesCurrentUserIfRegistrationSucceeds(): void
    {
        $this->assertGuest();

        $this->fromRoute('register')->post(route('register'), [
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();
    }

    /**
     * @testdox [POST register] [登録失敗] route('register') へリダイレクトする
     *
     * @group register
     */
    public function testPostRegisterRedirectsToRegisterPageIfRegistrationFails(): void
    {
        $response = $this->fromRoute('register')->post(route('register'), []);
        $response->assertRedirect('register');
    }

    /**
     * @testdox [POST register] [登録失敗] usersテーブルに何も保存しない
     *
     * @group register
     */
    public function testPostRegisterDoesntSaveAnythingToUsersTableIfRegistrationFails(): void
    {
        $this->assertDatabaseEmpty('users');
        $this->fromRoute('register')->post(route('register'), []);
        $this->assertDatabaseEmpty('users');
    }

    /**
     * @testdox [POST register] [登録失敗] 非認証状態を維持する
     *
     * @group register
     */
    public function testPostRegisterKeepsGuestIfRegistrationFails(): void
    {
        $this->assertGuest();
        $this->fromRoute('register')->post(route('register'), []);
        $this->assertGuest();
    }

    /**
     * @testdox [POST register] バリデーション
     *
     * @group register
     *
     * @dataProvider provideValidationTestParams
     */
    public function testPostRegisterValidates(string $field, $value, ?string $alert): void
    {
        $passwordConfirmation = $field === 'password' ? $value : null;

        $response = $this->fromRoute('register')->post(route('register'), [
            $field => $value,
            'password_confirmation' => $passwordConfirmation,
        ]);

        if (is_null($alert)) {
            $response->assertValid($field);
        } else {
            $response->assertInvalid([$field => $alert]);
        }
    }

    public static function provideValidationTestParams(): array
    {
        return [
            'name - 1文字 - OK' => ['name', 'a', null],
            'name - 191文字 - OK' => ['name', str_repeat('a', 191), null],
            'name - null - エラー' => ['name', null, '名前を入力してください'],
            'name - 非文字列 - エラー' => ['name', 123, '名前は文字列で入力してください'],
            'name - 192文字 - エラー' => ['name', str_repeat('a', 192), '名前は191文字以内で入力してください'],
            'email - ローカル1文字、ドメイン1文字 - OK' => ['email', 'a@a', null],
            'email - ローカル64文字、ドメイン63文字 - OK' => ['email', str_repeat('a', 64).'@'.str_repeat('a', 63), null],
            'email - null - エラー' => ['email', null, 'メールアドレスを入力してください'],
            'email - 非文字列 - エラー' => ['email', 123, '有効なメールアドレスを入力してください'],
            'email - 非メール形式 - エラー' => ['email', 'a', '有効なメールアドレスを入力してください'],
            'email - ローカル64文字、ドメイン64文字 - エラー' => ['email', str_repeat('a', 64).'@'.str_repeat('a', 64), '有効なメールアドレスを入力してください'],
            'password - 8文字 - OK' => ['password', str_repeat('a', 8), null],
            'password - 191文字 - OK' => ['password', str_repeat('a', 191), null],
            'password - null - エラー' => ['password', null, 'パスワードを入力してください'],
            'password - 非文字列 - エラー' => ['password', 123, 'パスワードは文字列で入力してください'],
            'password - 7文字 - エラー' => ['password', str_repeat('a', 7), 'パスワードは8文字以上で入力してください'],
            'password - 192文字 - エラー' => ['password', str_repeat('a', 192), 'パスワードは191文字以内で入力してください'],
        ];
    }

    /**
     * @testdox [POST register] メールアドレスが登録済みの場合、バリデーションエラーになる
     *
     * @group register
     */
    public function testPostRegisterWithAlreadyRegisteredEmailCausesValidationErrorForEmail(): void
    {
        User::create([
            'name' => 'a',
            'email' => 'test@example.com',
            'password' => Hash::make('password1'),
        ]);

        $response = $this->fromRoute('register')->post(route('register'), [
            'name' => 'b',
            'email' => 'test@example.com',
            'password' => 'password2',
            'password_confirmation' => 'password2',
        ]);
        $response->assertInvalid(['email' => '同じメールアドレスが既に登録されています']);
    }

    /**
     * @testdox [POST register] passwordとpassword_confirmationが一致しない場合、バリデーションエラーになる
     *
     * @group register
     */
    public function testPostRegisterWithUnmatchedPasswordCausesValidationErrorForPassword(): void
    {
        $response = $this->fromRoute('register')->post(route('register'), [
            'password' => 'password1',
            'password_confirmation' => 'password2',
        ]);
        $response->assertInvalid(['password' => '確認用パスワードと一致しません']);
    }
}

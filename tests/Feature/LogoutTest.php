<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    /**
     * @testdox [POST logout] [認証状態] 非認証状態になる
     *
     * @group logout
     */
    public function testPostLogoutForAuthUserMakesUserGuest(): void
    {
        $this->actingAs($this->user)->fromRoute('stamp')->post(route('logout'));
        $this->assertGuest();
    }

    /**
     * @testdox [POST logout] [認証状態] route('login') へリダイレクトする
     *
     * @group logout
     */
    public function testPostLogoutForAuthUserRedirectsToLoginPage(): void
    {
        $response = $this->actingAs($this->user)->fromRoute('stamp')->post(route('logout'));
        $response->assertRedirectToRoute('login');
    }

    /**
     * @testdox [POST logout] [非認証状態] route('login') へリダイレクトする
     *
     * @group logout
     */
    public function testPostLogoutForGuestRedirectsToLoginPage(): void
    {
        $response = $this->fromRoute('stamp')->post(route('logout'));
        $response->assertRedirectToRoute('login');
    }
}

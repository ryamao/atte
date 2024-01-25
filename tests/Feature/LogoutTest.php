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
     * @testdox [POST /logout] [認証状態] 非認証状態になる
     * @group logout
     */
    public function test_post_logout_for_auth_user_deactivate_authentication(): void
    {
        $this->actingAs($this->user)->from('/')->post('/logout');
        $this->assertGuest();
    }

    /**
     * @testdox [POST /logout] [認証状態] "/login" へリダイレクトする
     * @group logout
     */
    public function test_post_logout_for_auth_user_redirects_to_login_page(): void
    {
        $response = $this->actingAs($this->user)->from('/')->post('/logout');
        $response->assertRedirect('/login');
    }

    /**
     * @testdox [POST /logout] [非認証状態] "/login" へリダイレクトする
     * @group logout
     */
    public function test_post_logout_for_guest_redirects_to_login_page(): void
    {
        $response = $this->from('/')->post('/logout');
        $response->assertRedirect('/login');
    }
}

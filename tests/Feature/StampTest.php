<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StampTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox [GET /] [未認証状態] "/login" へリダイレクトする
     * @group stamp
     */
    public function test_get_stamping_for_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->get('/');
        $response->assertRedirect('/login');
    }

    /**
     * @testdox [GET /] [認証状態] ステータスコード200を返す
     * @group stamp
     */
    public function test_get_stamping_for_auth_user_returns_status_code_200(): void
    {
        $user = User::create([
            'name' => 'test',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);

        $response = $this->actingAs($user)->get('/');
        $response->assertStatus(200);
    }
}

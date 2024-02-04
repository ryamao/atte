<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class EmailVerifyTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    /** 各テストで使うログインユーザーを作成する */
    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['email_verified_at' => null]);
    }

    /**
     * @testdox [GET /email/verify] ステータスコード200を返す
     *
     * @group email-verify
     */
    public function testGetEmailVerifyReturnsStatusCode200(): void
    {
        $response = $this->actingAs($this->user)->fromRoute('register')->get(route('verification.notice'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST /email/verification-notice] メールが送信される
     *
     * @group email-verify
     */
    public function testPostEmailVerificationNoticeSendsEmail(): void
    {
        Notification::fake();

        $response = $this->actingAs($this->user)->fromRoute('verification.notice')->post(route('verification.send'));
        $response->assertRedirectToRoute('verification.notice');

        Notification::assertSentTo(
            $this->user,
            VerifyEmail::class,
            function (VerifyEmail $notification, $channels) {
                $mailMessage = $notification->toMail($this->user);
                $this->get($mailMessage->actionUrl);

                return true;
            },
        );

        $this->assertNotNull($this->user->fresh()->email_verified_at);
    }
}

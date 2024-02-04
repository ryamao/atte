<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;

class StampControllerTest extends StampControllerTestCase
{
    use RefreshDatabase;

    /**
     * @testdox [GET stamp] [未認証状態] route(stamp) へリダイレクトする
     *
     * @group stamp
     */
    public function testGetStampFromGuestRedirectsToLoginPage(): void
    {
        $this->assertGuest();
        $response = $this->get(route('stamp'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [GET stamp] [認証状態] ステータスコード200を返す
     *
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserReturnsStatusCode200(): void
    {
        $response = $this->actingAs($this->loginUser)->get(route('stamp'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST shift-begin] [未認証状態] route(login) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostShiftBeginFromGuestRedirectsToLoginPage(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-begin'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-begin] [認証状態] route(stamp) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserRedirectsToIndexPage(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-begin'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST shift-end] [未認証状態] route(login) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostShiftEndFromGuestRedirectsToLoginPage(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-end'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-end] [認証状態] route(stamp) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostShiftEndFromAuthenticatedUserRedirectsToIndexPage(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-end'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST break-begin] [未認証状態] route(login) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostBreakBeginFromGuestRedirectsToLoginPage(): void
    {
        $this->assertGuest();
        $response = $this->post(route('break-begin'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST break-begin] [認証状態] route(stamp) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostBreakBeginFromAuthenticatedUserRedirectsToIndexPage(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('break-begin'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST break-end] [未認証状態] route(login) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostBreakEndFromGuestRedirectsToLoginPage(): void
    {
        $this->assertGuest();
        $response = $this->post(route('break-end'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST break-end] [認証状態] route(stamp) へリダイレクトする
     *
     * @group stamp
     */
    public function testPostBreakEndFromAuthenticatedUserRedirectsToIndexPage(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('break-end'));
        $response->assertRedirect(route('stamp'));
    }
}

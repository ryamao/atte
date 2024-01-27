<?php

declare(strict_types=1);

namespace Tests\Feature;

class StampTest extends StampTestCase
{
    /**
     * @testdox [GET stamp] [未認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_get_stamp_from_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->get(route('stamp'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [GET stamp] [認証状態] ステータスコード200を返す
     * @group stamp
     */
    public function test_get_stamp_from_auth_user_returns_status_code_200(): void
    {
        $response = $this->actingAs($this->loginUser)->get(route('stamp'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [POST shift-begin] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_begin_from_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-begin'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-begin] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-begin'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST shift-end] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_end_from_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('shift-end'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST shift-end] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_shift_end_from_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('shift-end'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST break-begin] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_break_begin_from_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('break-begin'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST break-begin] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_break_begin_from_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('break-begin'));
        $response->assertRedirect(route('stamp'));
    }

    /**
     * @testdox [POST break-end] [未認証状態] route(login) へリダイレクトする
     * @group stamp
     */
    public function test_post_break_end_from_guest_redirects_to_login_page(): void
    {
        $this->assertGuest();
        $response = $this->post(route('break-end'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [POST break-end] [認証状態] route(stamp) へリダイレクトする
     * @group stamp
     */
    public function test_post_break_end_from_auth_user_redirects_to_index_page(): void
    {
        $response = $this->actingAs($this->loginUser)->post(route('break-end'));
        $response->assertRedirect(route('stamp'));
    }
}

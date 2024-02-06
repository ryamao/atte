<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @see \App\Http\Controllers\UserController
 *
 * @group users
 */
class UserControllerIndexTest extends TestCase
{
    use RefreshDatabase;

    /** ログインに使用する会員 */
    private User $user;

    /** 会員と勤怠情報を作成する */
    protected function setUp(): void
    {
        parent::setUp();

        $today = CarbonImmutable::create(year: 2024, month: 2, day: 8, tz: 'Asia/Tokyo');
        CarbonImmutable::setTestNow($today);

        foreach (range(1, 99) as $i) {
            User::factory()->create(['name' => sprintf('user%02d', fake()->numberBetween(1, 99))]);
        }
        $this->user = User::first();
    }

    /**
     * @testdox [GET /users] [非認証状態] route('login') へリダイレクトする
     */
    public function testGetUsersFromGuestRedirectsToLoginPage(): void
    {
        $response = $this->get(route('users.index'));
        $response->assertRedirect(route('login'));
    }

    /**
     * @testdox [GET /users] [認証済み] ステータスコード200を返す
     */
    public function testGetUsersReturnsStatusCode200(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.index'));
        $response->assertStatus(200);
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件なし] [ページ指定なし] 名前順で先頭から12件取得する
     */
    public function testGetUsersReturnsFirstTwelveUsersOrderedByName(): void
    {
        $expected = User::orderBy('name')->orderBy('id')->take(12)->pluck('name');
        $response = $this->actingAs($this->user)->get(route('users.index'));
        $actual = $response->viewData('users')->pluck('name');
        $this->assertSameSize($expected, $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件なし] [ページ指定あり] 名前順で (ページ番号 * 12) 件目から12件取得する
     */
    public function testGetUsersReturnsTwelveUsersOrderedByNameFromPageNumberTimesTwelve(): void
    {
        $expectedList = User::orderBy('name')->orderBy('id')->pluck('name')->chunk(12);

        for ($page = 1; $page <= $expectedList->count(); $page++) {
            $expected = $expectedList->get($page - 1)->values();
            $response = $this->actingAs($this->user)->get(route('users.index', ['page' => $page]));
            $actual = $response->viewData('users')->pluck('name');
            $this->assertSameSize($expected, $actual);
            $this->assertEquals($expected, $actual);
        }
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件なし] [ページ指定あり] ページ番号が0以下の場合は先頭のページを表示する
     */
    public function testGetUsersReturnsFirstPageWhenPageNumberIsZeroOrLess(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.index', ['page' => 0]));
        $actual = $response->viewData('users')->pluck('name');
        $expected = User::orderBy('name')->orderBy('id')->take(12)->pluck('name');
        $this->assertSameSize($expected, $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件なし] [ページ指定あり] ページ番号が最大ページ数を超える場合は空のコレクションを返す
     */
    public function testGetUsersReturnsEmptyCollectionWhenPageNumberExceedsMaxPage(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.index', ['page' => 100]));
        $actual = $response->viewData('users');
        $this->assertCount(0, $actual);
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件あり] [ページ指定なし] 検索条件に一致する名前順で先頭から12件取得する
     */
    public function testGetUsersReturnsFirstTwelveUsersOrderedByNameMatchingSearchQuery(): void
    {
        $searchQuery = 'user9';
        $expected = User::where('name', 'like', "%{$searchQuery}%")->orderBy('name')->orderBy('id')->take(12)->pluck('name');
        $response = $this->actingAs($this->user)->get(route('users.index', ['search' => $searchQuery]));
        $actual = $response->viewData('users')->pluck('name');
        $this->assertSameSize($expected, $actual);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @testdox [GET /users] [認証済み] [検索条件あり] [ページ指定あり] 検索条件に一致する名前順で (ページ番号 * 12) 件目から12件取得する
     */
    public function testGetUsersReturnsTwelveUsersOrderedByNameMatchingSearchQueryFromPageNumberTimesTwelve(): void
    {
        $searchQuery = 'user8';
        $expectedList = User::where('name', 'like', "%{$searchQuery}%")->orderBy('name')->orderBy('id')->pluck('name')->chunk(12);

        for ($page = 1; $page <= $expectedList->count(); $page++) {
            $expected = $expectedList->get($page - 1)->values();
            $response = $this->actingAs($this->user)->get(route('users.index', ['search' => $searchQuery, 'page' => $page]));
            $actual = $response->viewData('users')->pluck('name');
            $this->assertSameSize($expected, $actual);
            $this->assertEquals($expected, $actual);
        }
    }
}

<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class UsersIndexPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = ['users', 'sessions'];

    /** ログインに使用する会員 */
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * @textdox [会員一覧ページ] [表示] "$selector" - "$expected"
     *
     * @group users
     *
     * @testWith ["header h1", "Atte"]
     *           ["header nav li:nth-child(1)", "ホーム"]
     *           ["header nav li:nth-child(2)", "日付一覧"]
     *           ["header nav li:nth-child(3)", "会員一覧"]
     *           ["header nav li:nth-child(4)", "ログアウト"]
     *           ["footer", "Atte, inc."]
     */
    public function testUsersIndexPageHasText(string $selector, string $expected): void
    {
        $this->browse(function (Browser $browser) use ($selector, $expected) {
            $browser->loginAs($this->user);
            $browser->visitRoute('users.index');
            $text = $browser->text($selector);
            $this->assertEquals($expected, $text);
        });
    }
}

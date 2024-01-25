<?php

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StampPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    protected $tablesToTruncate = ['users', 'sessions'];

    private User $user;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    private function browseAfterLogin(callable $callback): void
    {
        $this->browse(function (Browser $browser) use ($callback) {
            $browser->loginAs($this->user);
            try {
                $callback($browser);
            } finally {
                $browser->logout();
            }
        });
    }

    /**
     * @testdox [打刻ページ] [テキスト] "$selector" に "$text" が表示されている
     * @group stamp
     * @testWith ["header h1", "Atte"]
     *           ["footer small", "Atte, inc."]
     */
    public function test_login_page_has_text(string $selector, string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($selector, $text) {
            $browser->visit('/');
            $browser->assertSeeIn($selector, $text);
        });
    }

    /**
     * @testdox [打刻ページ] [テキスト] ログインユーザに対するねぎらいの言葉が表示されている
     * @group stamp
     */
    public function test_login_page_has_gratitude_text_for_current_user(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visit('/');
            $browser->assertSeeIn('@gratitude', "{$this->user->name}さんお疲れ様です！");
        });
    }

    /**
     * @testdox [打刻ページ] [リンク] "$text" が表示されている
     * @group stamp
     * @testWith ["ホーム"]
     *           ["日付一覧"]
     */
    public function test_login_page_has_link(string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($text) {
            $browser->visit('/');
            $browser->assertSeeLink($text);
        });
    }

    /**
     * @testdox [打刻ページ] [ボタン] "$text" が表示されている
     * @group stamp
     * @testWith ["@logout", "ログアウト"]
     *           ["@shift-begin", "勤務開始"]
     *           ["@shift-end", "勤務終了"]
     *           ["@break-begin", "休憩開始"]
     *           ["@break-end", "休憩終了"]
     */
    public function test_login_page_has_button(string $selector, string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($selector, $text) {
            $browser->visit('/');
            $browser->assertSeeIn($selector, $text);
        });
    }
}

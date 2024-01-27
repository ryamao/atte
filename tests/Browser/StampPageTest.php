<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class StampPageTest extends DuskTestCase
{
    use DatabaseTruncation;

    /** ログインに使用するユーザ */
    private User $user;

    /** 各テストケース前に実行する。 */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'テスト太郎',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
        ]);
    }

    /** ログイン状態でテストを開始するためのラッパー関数。 */
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
    public function test_stamp_page_has_text(string $selector, string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($selector, $text) {
            $browser->visitRoute('stamp');
            $browser->assertSeeIn($selector, $text);
        });
    }

    /**
     * @testdox [打刻ページ] [テキスト] ログインユーザに対するねぎらいの言葉が表示されている
     * @group stamp
     */
    public function test_stamp_page_has_gratitude_text_for_current_user(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->assertSeeIn('@gratitude', "{$this->user->name}さんお疲れ様です！");
        });
    }

    /**
     * @testdox [打刻ページ] [リンク] "$link" が表示されている
     * @group stamp
     * @testWith ["ホーム"]
     *           ["日付一覧"]
     */
    public function test_stamp_page_has_link(string $link): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($link) {
            $browser->visitRoute('stamp');
            $browser->assertSeeLink($link);
        });
    }

    /**
     * @testdox [打刻ページ] [リンク] "$link" クリック時の遷移先が "$routeName"
     * @group stamp
     * @testWith ["ホーム", "stamp"]
     *           ["日付一覧", "attendance"]
     */
    public function test_stamp_page_links(string $link, string $routeName): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($link, $routeName) {
            $browser->visitRoute('stamp');
            $browser->clickLink($link);
            $browser->assertRouteIs($routeName);
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
    public function test_stamp_page_has_button(string $selector, string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($selector, $text) {
            $browser->visitRoute('stamp');
            $browser->assertSeeIn($selector, $text);
        });
    }

    /**
     * @testdox [打刻ページ] [ボタン] ログアウト
     * @group stamp
     */
    public function test_stamp_page_logout_button(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('ログアウト');
            $browser->assertRouteIs('login');
            $this->assertGuest();
        });
    }

    /**
     * @testdox [打刻ページ] [勤務前] ボタンの有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_before_work(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->assertButtonEnabled('勤務開始');
            $browser->assertButtonDisabled('勤務終了');
            $browser->assertButtonDisabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [勤務中] ボタン押下時の遷移と有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_during_work(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('勤務開始');
            $browser->assertRouteIs('stamp');
            $browser->assertButtonDisabled('勤務開始');
            $browser->assertButtonEnabled('勤務終了');
            $browser->assertButtonEnabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [勤務後] ボタン押下時の遷移と有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_after_work(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('勤務開始');
            $browser->press('勤務終了');
            $browser->assertRouteIs('stamp');
            $browser->assertButtonEnabled('勤務開始');
            $browser->assertButtonDisabled('勤務終了');
            $browser->assertButtonDisabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [勤務再開後] ボタン押下時の遷移と有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_during_work_again(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('勤務開始');
            $browser->press('勤務終了');
            $browser->press('勤務開始');
            $browser->assertRouteIs('stamp');
            $browser->assertButtonDisabled('勤務開始');
            $browser->assertButtonEnabled('勤務終了');
            $browser->assertButtonEnabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [休憩中] ボタン押下時の遷移と有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_during_break(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('勤務開始');
            $browser->press('休憩開始');
            $browser->assertRouteIs('stamp');
            $browser->assertButtonDisabled('勤務開始');
            $browser->assertButtonDisabled('勤務終了');
            $browser->assertButtonDisabled('休憩開始');
            $browser->assertButtonEnabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [休憩後] ボタン押下時の遷移と有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_after_break(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visitRoute('stamp');
            $browser->press('勤務開始');
            $browser->press('休憩開始');
            $browser->press('休憩終了');
            $browser->assertRouteIs('stamp');
            $browser->assertButtonDisabled('勤務開始');
            $browser->assertButtonEnabled('勤務終了');
            $browser->assertButtonEnabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }
}

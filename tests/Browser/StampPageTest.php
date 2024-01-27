<?php

namespace Tests\Browser;

use App\Models\BreakBegin;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
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
            $browser->visit('/');
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
    public function test_stamp_page_has_link(string $text): void
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
    public function test_stamp_page_has_button(string $selector, string $text): void
    {
        $this->browseAfterLogin(function (Browser $browser) use ($selector, $text) {
            $browser->visit('/');
            $browser->assertSeeIn($selector, $text);
        });
    }

    /**
     * @testdox [打刻ページ] [勤務前] ボタンの有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_before_work(): void
    {
        $this->browseAfterLogin(function (Browser $browser) {
            $browser->visit('/');
            $browser->assertButtonEnabled('勤務開始');
            $browser->assertButtonDisabled('勤務終了');
            $browser->assertButtonDisabled('休憩開始');
            $browser->assertButtonDisabled('休憩終了');
        });
    }

    /**
     * @testdox [打刻ページ] [勤務中] ボタンの有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_during_work(): void
    {
        $begunAt = CarbonImmutable::now(new DateTimeZone('Asia/Tokyo'));
        ShiftBegin::create(['user_id' => $this->user->id, 'begun_at' => $begunAt]);
        $this->travelTo($begunAt->addHour(), function () {
            $this->browseAfterLogin(function (Browser $browser) {
                $browser->visit('/');
                $browser->assertButtonDisabled('勤務開始');
                $browser->assertButtonEnabled('勤務終了');
                $browser->assertButtonEnabled('休憩開始');
                $browser->assertButtonDisabled('休憩終了');
            });
        });
    }

    /**
     * @testdox [打刻ページ] [勤務後] ボタンの有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_after_work(): void
    {
        $begunAt = CarbonImmutable::now(new DateTimeZone('Asia/Tokyo'));
        $endedAt = $begunAt->addHours(8);
        ShiftTiming::create(['user_id' => $this->user->id, 'begun_at' => $begunAt, 'ended_at' => $endedAt]);
        $this->travelTo($endedAt->addHour(), function () {
            $this->browseAfterLogin(function (Browser $browser) {
                $browser->visit('/');
                $browser->assertButtonEnabled('勤務開始');
                $browser->assertButtonDisabled('勤務終了');
                $browser->assertButtonDisabled('休憩開始');
                $browser->assertButtonDisabled('休憩終了');
            });
        });
    }

    /**
     * @testdox [打刻ページ] [休憩中] ボタンの有効化・無効化
     * @group stamp
     */
    public function test_stamp_page_buttons_during_break(): void
    {
        $begunAt = CarbonImmutable::now(new DateTimeZone('Asia/Tokyo'));
        ShiftBegin::create(['user_id' => $this->user->id, 'begun_at' => $begunAt]);
        BreakBegin::create(['user_id' => $this->user->id, 'begun_at' => $begunAt->addHours(3)]);
        $this->travelTo($begunAt->addHours(4), function () {
            $this->browseAfterLogin(function (Browser $browser) {
                $browser->visit('/');
                $browser->assertButtonDisabled('勤務開始');
                $browser->assertButtonDisabled('勤務終了');
                $browser->assertButtonDisabled('休憩開始');
                $browser->assertButtonEnabled('休憩終了');
            });
        });
    }
}

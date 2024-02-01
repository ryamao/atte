<?php

declare(strict_types=1);

namespace Tests\Browser;

use App\Models\BreakTiming;
use App\Models\ShiftTiming;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/** 日付別勤怠ページのブラウザテスト */
class AttendancePageTest extends DuskTestCase
{
    use DatabaseTruncation;

    /** @var \Illuminate\Database\Eloquent\Collection<\App\Models\User> */
    protected $users;

    /**
     * テストデータ作成
     *
     * - 1ページ5名なので3ページ分
     * - 3ページ目が半端な人数になるようにする
     * - 当日と前日の2日分
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->users = User::factory(5 + 5 + 3)->create();
        foreach ([today(), today()->subDay()] as $date) {
            $this->travelTo($date, function () {
                $this->users->each(function ($user) {
                    $user->shiftTimings()->save(ShiftTiming::factory()->make());
                    $user->shiftTimings()->save(BreakTiming::factory()->make());
                    $user->shiftTimings()->save(BreakTiming::factory()->make());
                });
            });
        }
    }

    /**
     * @testdox [日付別勤怠ページ] "/attendance" にアクセスできる
     *
     * @group attendance
     */
    public function testAttendancePageIsAccessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertPathIs('/attendance');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [テキスト] "$selector" に "$text" が表示されている
     *
     * @group attendance
     *
     * @testWith ["header h1", "Atte"]
     *           ["@logout", "ログアウト"]
     *           ["footer small", "Atte, inc."]
     */
    public function testAttendancePageHasText(string $selector, string $text): void
    {
        $this->browse(function (Browser $browser) use ($selector, $text) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeIn($selector, $text);
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [リンク/ボタン] "$link" が表示されていて、クリック時に route('$routeName') に遷移する
     *
     * @group attendance
     *
     * @testWith ["ホーム", "stamp"]
     *           ["日付一覧", "attendance"]
     */
    public function testAttendancePageHasLink(string $link, string $routeName): void
    {
        $this->browse(function (Browser $browser) use ($link, $routeName) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeLink($link);
            $browser->clickLink($link);
            $browser->assertRouteIs($routeName);
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [リンク/ボタン] "ログアウト" を押すとログアウトする
     *
     * @group attendance
     */
    public function testAttendancePageHasLogoutLink(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->press('ログアウト');
            $browser->assertRouteIs('login');
            $this->assertGuest();
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [日付] 当日の日付が表示されている
     *
     * @group attendance
     */
    public function testAttendancePageHasCurrentDate(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeIn('@current-date', today()->format('Y-m-d'));
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [日付] 前日リンクが存在していて、押下時に前日の勤怠ページに遷移する
     *
     * @group attendance
     */
    public function testAttendancePageHasPreviousDateLink(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertPresent('@previous-date');
            $browser->click('@previous-date');
            $browser->assertSeeIn('@current-date', today()->subDay()->format('Y-m-d'));
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [日付] 翌日リンクが存在していて、押下時に翌日の勤怠ページに遷移する
     *
     * @group attendance
     */
    public function testAttendancePageHasNextDateLink(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertPresent('@next-date');
            $browser->click('@next-date');
            $browser->assertSeeIn('@current-date', today()->addDay()->format('Y-m-d'));
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] 左から $n 番目のヘッダにテキスト "$text" が表示されている
     *
     * @group attendance
     *
     * @testWith [1, "名前"]
     *           [2, "勤務開始"]
     *           [3, "勤務終了"]
     *           [4, "休憩時間"]
     *           [5, "勤務時間"]
     */
    public function testAttendancePageHasTableHeader(int $n, string $text): void
    {
        $this->browse(function (Browser $browser) use ($n, $text) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeIn("main table thead th:nth-child($n)", $text);
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] 13名分のデータが1ページ5名ずつで3ページ表示されている
     *
     * @group attendance
     */
    public function testAttendancePageHasCorrectNumberOfRows(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            foreach (range(1, 3) as $page) {
                $browser->visitRoute('attendance', ['page' => $page]);

                $users = $this->users->sortBy(['name', 'id'])->slice(($page - 1) * 5, 5)->values();
                foreach ($users as $i => $user) {
                    $row = $i + 1;
                    $actual = $browser->text("main table tbody tr:nth-child($row) td:nth-child(1)");
                    $this->assertSame($user->name, $actual);
                }

                $row = $users->count() + 1;
                $this->assertThrows(
                    fn () => $browser->text("main table tbody tr:nth-child($row) td:nth-child(1)"),
                    \Facebook\WebDriver\Exception\NoSuchElementException::class,
                );
            }
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] 時刻と時間の表示形式が正しい
     *
     * @group attendance
     */
    public function testAttendancePageHasCorrectShiftTimingFormat(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            foreach (range(2, 5) as $column) {
                $this->assertMatchesRegularExpression(
                    '/^\d{2}:\d{2}:\d{2}$/',
                    $browser->text("main table tbody tr:nth-child(1) td:nth-child($column)"),
                );
            }
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] 勤務終了していないユーザの終了時刻と勤務時間が "--:--:--" である
     *
     * @group attendance
     */
    public function testAttendancePageHasCorrectWorkTimeForUserWhoHasNotEndedShift(): void
    {
        $this->users->each(function ($user) {
            $user->shiftTimings()->update(['ended_at' => null]);
        });

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeIn('main table tbody tr:nth-child(1) td:nth-child(3)', '--:--:--');
            $browser->assertSeeIn('main table tbody tr:nth-child(1) td:nth-child(5)', '--:--:--');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] 休憩終了していないユーザの休憩時間が "--:--:--" である
     *
     * @group attendance
     */
    public function testAttendancePageHasCorrectBreakTimeForUserWhoHasNotEndedBreak(): void
    {
        $this->users->each(function ($user) {
            $user->breakTimings()->update(['ended_at' => null]);
        });

        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertSeeIn('main table tbody tr:nth-child(1) td:nth-child(4)', '--:--:--');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] ページネーションが正しく表示される
     *
     * @group attendance
     */
    public function testAttendancePageHasPagination(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->assertPresent('main nav[aria-label="'.__('Pagination Navigation').'"]');
            $browser->assertPresent('main nav [aria-label="'.__('pagination.previous').'"]:not(a)');
            $browser->assertPresent('main nav a[aria-label="'.__('pagination.next').'"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '1');
            $browser->assertSeeIn('main nav a[aria-label="'.__('Go to page :page', ['page' => 2]).'"]', '2');
            $browser->assertSeeIn('main nav a[aria-label="'.__('Go to page :page', ['page' => 3]).'"]', '3');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] [勤怠テーブル] ページネーションのリンクが正しく機能する
     *
     * @group attendance
     */
    public function testAttendancePageHasPaginationLinks(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs($this->users->first());
            $browser->visitRoute('attendance');
            $browser->click('main nav a[aria-label="'.__('pagination.next').'"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '2');
            $browser->click('main nav a[aria-label="'.__('Go to page :page', ['page' => 3]).'"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '3');
            $browser->click('main nav a[aria-label="'.__('pagination.previous').'"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '2');
            $browser->click('main nav a[aria-label="'.__('Go to page :page', ['page' => 1]).'"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '1');
        });
    }
}

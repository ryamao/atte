<?php

namespace Tests\Browser;

use App\Models\ShiftTiming;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

/** 日付別勤怠ページのブラウザテスト */
class AttendancePageTest extends DuskTestCase
{
    use DatabaseTruncation;

    /**
     * @testdox [日付別勤怠ページ] "/attendance" にアクセスできる
     * @group attendance
     */
    public function testAttendancePageIsAccessible(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create());
            $browser->visitRoute('attendance');
            $browser->assertPathIs('/attendance');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] 必要な項目が存在する
     * @group attendance
     */
    public function testAttendancePageHasText(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create());
            $browser->visitRoute('attendance');

            $browser->assertSeeIn('header h1', 'Atte');

            $browser->assertSeeLink('ホーム');
            $browser->assertSeeLink('日付一覧');
            $browser->assertSeeIn('@logout', 'ログアウト');

            $browser->assertSeeIn('@current-date', today()->format('Y-m-d'));
            $browser->assertPresent('@previous-date');
            $browser->assertPresent('@next-date');

            $browser->assertSeeIn('main table thead th:nth-child(1)', '名前');
            $browser->assertSeeIn('main table thead th:nth-child(2)', '勤務開始');
            $browser->assertSeeIn('main table thead th:nth-child(3)', '勤務終了');
            $browser->assertSeeIn('main table thead th:nth-child(4)', '休憩時間');
            $browser->assertSeeIn('main table thead th:nth-child(5)', '勤務時間');

            $browser->assertSeeIn('footer small', 'Atte, inc.');
        });
    }

    /**
     * @testdox [日付別勤怠ページ] 前日リンクを押すと前日の勤怠ページに遷移する
     * @group attendance
     */
    public function testAttendancePageHasPreviousDateLink(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create());
            $browser->visitRoute('attendance');
            $browser->click('@previous-date');
            $browser->assertSeeIn('@current-date', today()->subDay()->format('Y-m-d'));
        });
    }

    /**
     * @testdox [日付別勤怠ページ] 翌日リンクを押すと翌日の勤怠ページに遷移する
     * @group attendance
     */
    public function testAttendancePageHasNextDateLink(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->loginAs(User::factory()->create());
            $browser->visitRoute('attendance');
            $browser->click('@next-date');
            $browser->assertSeeIn('@current-date', today()->addDay()->format('Y-m-d'));
        });
    }

    /**
     * @testdox [日付別勤怠ページ] 1ページ目に5名分の勤怠情報が表示されている
     * @group attendance
     */
    public function testAttendancePageHasFiveAttendanceData(): void
    {
        $users = User
            ::factory()
            ->count(8)
            ->has(ShiftTiming::factory()->count(1))
            ->create()
            ->sortBy('name')
            ->take(5)
            ->values();

        $this->browse(function (Browser $browser) use ($users) {
            $browser->loginAs($users[0]);
            $browser->visitRoute('attendance');
            foreach ($users as $i => $user) {
                $n = $i + 1;
                $browser->assertSeeIn("main table tbody tr:nth-child($n) td:nth-child(1)", $user->name);
            }
            $this->assertThrows(
                fn () => $browser->text("main table tbody tr:nth-child(6) td:nth-child(1)"),
                \Facebook\WebDriver\Exception\NoSuchElementException::class,
            );
        });
    }

    /**
     * @testdox [日付別勤怠ページ] 2ページ目に3名分の勤怠情報が表示されている
     * @group attendance
     */
    public function testAttendancePageHasThreeAttendanceDataOnSecondPage(): void
    {
        $users = User
            ::factory()
            ->count(8)
            ->has(ShiftTiming::factory()->count(1))
            ->create()
            ->sortBy('name')
            ->slice(5)
            ->values();

        $this->browse(function (Browser $browser) use ($users) {
            $browser->loginAs($users[0]);
            $browser->visitRoute('attendance', ['page' => 2]);
            foreach ($users as $i => $user) {
                $n = $i + 1;
                $browser->assertSeeIn("main table tbody tr:nth-child($n) td:nth-child(1)", $user->name);
            }
            $this->assertThrows(
                fn () => $browser->text('main table tbody tr:nth-child(4) td:nth-child(1)'),
                \Facebook\WebDriver\Exception\NoSuchElementException::class,
            );
        });
    }

    /**
     * @testdox [日付別勤怠ページ] ページネーションの表示とリンクが正しい
     * @group attendance
     */
    public function testAttendancePageHasPagination(): void
    {
        $users = User::factory()->count(103)->has(ShiftTiming::factory())->create();

        $this->browse(function (Browser $browser) use ($users) {
            $browser->loginAs($users[0]);
            $browser->visitRoute('attendance');

            $browser->assertPresent('main nav[aria-label="' . __('Pagination Navigation') . '"]');
            $browser->assertPresent('main nav [aria-label="' . __('pagination.previous') . '"]:not(a)');
            $browser->assertPresent('main nav a[aria-label="' . __('pagination.next') . '"]');

            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '1');
            foreach (range(2, 21) as $page) {
                if (11 <= $page && $page <= 19) {
                    $this->assertThrows(
                        fn () => $browser->text('main nav a[aria-label="' . __('Go to page :page', ['page' => $page]) . '"]'),
                        \Facebook\WebDriver\Exception\NoSuchElementException::class,
                    );
                } else {
                    $browser->assertSeeIn('main nav a[aria-label="' . __('Go to page :page', ['page' => $page]) . '"]', $page);
                }
            }

            $browser->click('main nav a[aria-label="' . __('pagination.next') . '"]');
            $browser->assertPresent('main nav a[aria-label="' . __('pagination.previous') . '"]');
            $browser->assertPresent('main nav a[aria-label="' . __('pagination.next') . '"]');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '2');

            $browser->click('main nav a[aria-label="' . __('Go to page :page', ['page' => 21]) . '"]');
            $browser->assertPresent('main nav a[aria-label="' . __('pagination.previous') . '"]');
            $browser->assertPresent('main nav [aria-label="' . __('pagination.next') . '"]:not(a)');
            $browser->assertSeeIn('main nav [aria-current="page"]:not(a)', '21');
        });
    }
}

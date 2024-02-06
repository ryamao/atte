<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use App\Services\UserAttendanceService;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * 会員別の勤怠情報を取得するサービスのテスト
 *
 * このサービスでは、会員と年月を指定して勤怠情報を取得する。
 * 指定された年月内の日付ごとに、個別会員の勤務時間/休憩時間/労働時間を秒数で取得する。
 * 用意されているメソッドは以下の4つである。
 *
 * - `UserAttendanceService::calendarDates(): \Illuminate\Database\Eloquent\Builder` ... 指定年月内の日付を取得する
 * - `UserAttendanceService::shiftTimesInSeconds(): \Illuminate\Database\Eloquent\Builder` ... 勤務時間を秒数で取得する
 * - `UserAttendanceService::breakTimesInSeconds(): \Illuminate\Database\Eloquent\Builder` ... 休憩時間を秒数で取得する
 * - `UserAttendanceService::attendances(): \Illuminate\Database\Eloquent\Builder` ... 勤怠情報を取得する
 *
 * @see \App\Services\UserAttendanceService
 *
 * @group users
 */
class UserAttendanceServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** システム日付 */
    private CarbonImmutable $today;

    /** 1人目の会員(5勤2休) */
    private User $user1;

    /** 2人目の会員(ランダム) */
    private User $user2;

    /** 3人目の会員(勤務無し) */
    private User $user3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::create(year: 2024, month: 2, day: 5, tz: 'Asia/Tokyo');

        // 3人の会員と勤怠情報を作成する
        // 1月1日から2月4日までの勤怠情報を作成する
        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();
        $this->user3 = User::factory()->create();
        foreach ($this->today->subMonth()->firstOfMonth()->daysUntil($this->today->subDay()) as $date) {
            $this->travelTo($date, function () use ($date) {
                if ($date->isWeekday()) {
                    ShiftTiming::factory()->recycle($this->user1)->create();
                    BreakTiming::factory(2)->recycle($this->user1)->create();
                }
                if ($this->faker->numberBetween(1, 7) > 2) {
                    ShiftTiming::factory()->recycle($this->user2)->create();
                    BreakTiming::factory(2)->recycle($this->user2)->create();
                }
            });
        }

        // システム日付を2024年2月5日に設定する
        CarbonImmutable::setTestNow($this->today);

        // 2月5日に勤務開始した会員を作成する
        ShiftBegin::factory()->recycle($this->user1)->create();
        BreakBegin::factory()->recycle($this->user1)->create();
        ShiftBegin::factory()->recycle($this->user2)->create();
    }

    /**
     * @testdox [日付の取得] [前月] 1月分の日付を取得する
     */
    public function testCalendarDatesReturnsAllDatesOfPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $calendarDates = $service->calendarDates()->orderBy('date')->get();
        $this->assertCount($previousMonth->daysInMonth, $calendarDates);
        for ($i = 1; $i <= $previousMonth->daysInMonth; $i++) {
            $this->assertEquals($previousMonth->setDay($i), $calendarDates[$i - 1]->date);
        }
    }

    /**
     * @testdox [日付の取得] [当月] 1日から当日までの日付を取得する
     */
    public function testCalendarDatesReturnsAllDatesFromFirstDayToToday(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $calendarDates = $service->calendarDates()->orderBy('date')->get();
        $this->assertCount(5, $calendarDates);
        for ($i = 1; $i <= 5; $i++) {
            $this->assertEquals($this->today->setDay($i), $calendarDates[$i - 1]->date);
        }
    }

    /**
     * @testdox [日付の取得] [翌月] 結果の件数が 0 件である
     */
    public function testCalendarDatesReturnsZeroResultsWhenNextMonth(): void
    {
        $nextMonth = $this->today->addMonth();
        $service = new UserAttendanceService($this->user1, $nextMonth);
        $calendarDates = $service->calendarDates()->orderBy('date')->get();
        $this->assertCount(0, $calendarDates);
    }

    /**
     * @testdox [日付の取得] [当月] 翌日になると結果の件数が1件増える
     */
    public function testCalendarDatesReturnsOneMoreResultWhenNextDay(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $calendarDates = $service->calendarDates()->orderBy('date')->get();
        $this->assertCount(5, $calendarDates);
        CarbonImmutable::setTestNow($this->today->addDay());
        $calendarDates = $service->calendarDates()->orderBy('date')->get();
        $this->assertCount(6, $calendarDates);
    }

    /**
     * @testdox [勤務時間の取得] [前月] 結果の件数が31件である
     */
    public function testShiftSecondsReturns31ResultsWhenPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        $this->assertCount($previousMonth->daysInMonth, $shiftTimes);
    }

    /**
     * @testdox [勤務時間の取得] [当月] 結果の件数が4件である
     */
    public function testShiftSecondsReturns5ResultsWhenCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        $this->assertCount(4, $shiftTimes);
    }

    /**
     * @testdox [勤務時間の取得] [翌月] 結果の件数が 0 件である
     */
    public function testShiftSecondsReturnsZeroResultsWhenNextMonth(): void
    {
        $nextMonth = $this->today->addMonth();
        $service = new UserAttendanceService($this->user1, $nextMonth);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        $this->assertCount(0, $shiftTimes);
    }

    /**
     * @testdox [勤務時間の取得] [前月] 指定年月に勤務していない場合、0秒を返す
     */
    public function testShiftSecondsReturnsZeroSecondsWhenUserDidNotWorkInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user3, $previousMonth);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        foreach ($shiftTimes as $shiftTime) {
            $this->assertEquals(0, $shiftTime->shift_seconds);
        }
    }

    /**
     * @testdox [勤務時間の取得] [当日] 指定年月に勤務していない場合、0秒を返す
     */
    public function testShiftSecondsReturnsZeroSecondsWhenUserDidNotWorkInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user3, $this->today);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        foreach ($shiftTimes as $shiftTime) {
            $this->assertEquals(0, $shiftTime->shift_seconds);
        }
    }

    /**
     * @testdox [勤務時間の取得] [前月] 指定年月に勤務している場合、勤務時間を秒数で取得する
     */
    public function testShiftSecondsReturnsWorkSecondsWhenUserWorkedInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        foreach ($shiftTimes as $shiftTime) {
            if ($shiftTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $shiftTime->shift_seconds);
            } else {
                $this->assertEquals(0, $shiftTime->shift_seconds);
            }
        }
    }

    /**
     * @testdox [勤務時間の取得] [当月] 指定年月に勤務している場合、勤務時間を秒数で取得する
     */
    public function testShiftSecondsReturnsWorkSecondsWhenUserWorkedInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $shiftTimes = $service->shiftTimesInSeconds()->get();
        foreach ($shiftTimes as $shiftTime) {
            if ($shiftTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $shiftTime->shift_seconds);
            } else {
                $this->assertEquals(0, $shiftTime->shift_seconds);
            }
        }
    }

    /**
     * @testdox [休憩時間の取得] [前月] 結果の件数が31件である
     */
    public function testBreakSecondsReturns31ResultsWhenPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $breakTimes = $service->breakTimesInSeconds()->get();
        $this->assertCount($previousMonth->daysInMonth, $breakTimes);
    }

    /**
     * @testdox [休憩時間の取得] [当月] 結果の件数が4件である
     */
    public function testBreakSecondsReturns5ResultsWhenCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $breakTimes = $service->breakTimesInSeconds()->get();
        $this->assertCount(4, $breakTimes);
    }

    /**
     * @testdox [休憩時間の取得] [翌月] 結果の件数が 0 件である
     */
    public function testBreakSecondsReturnsZeroResultsWhenNextMonth(): void
    {
        $nextMonth = $this->today->addMonth();
        $service = new UserAttendanceService($this->user1, $nextMonth);
        $breakTimes = $service->breakTimesInSeconds()->get();
        $this->assertCount(0, $breakTimes);
    }

    /**
     * @testdox [休憩時間の取得] [前月] 指定年月に休憩していない場合、0秒を返す
     */
    public function testBreakSecondsReturnsZeroSecondsWhenUserDidNotTakeABreakInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user3, $previousMonth);
        $breakTimes = $service->breakTimesInSeconds()->get();
        foreach ($breakTimes as $breakTime) {
            $this->assertEquals(0, $breakTime->break_seconds);
        }
    }

    /**
     * @testdox [休憩時間の取得] [当日] 指定年月に休憩していない場合、0秒を返す
     */
    public function testBreakSecondsReturnsZeroSecondsWhenUserDidNotTakeABreakInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user3, $this->today);
        $breakTimes = $service->breakTimesInSeconds()->get();
        foreach ($breakTimes as $breakTime) {
            $this->assertEquals(0, $breakTime->break_seconds);
        }
    }

    /**
     * @testdox [休憩時間の取得] [前月] 指定年月に休憩している場合、休憩時間を秒数で取得する
     */
    public function testBreakSecondsReturnsBreakSecondsWhenUserTookABreakInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $breakTimes = $service->breakTimesInSeconds()->get();
        foreach ($breakTimes as $breakTime) {
            if ($breakTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $breakTime->break_seconds);
            } else {
                $this->assertEquals(0, $breakTime->break_seconds);
            }
        }
    }

    /**
     * @testdox [休憩時間の取得] [当月] 指定年月に休憩している場合、休憩時間を秒数で取得する
     */
    public function testBreakSecondsReturnsBreakSecondsWhenUserTookABreakInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $breakTimes = $service->breakTimesInSeconds()->get();
        foreach ($breakTimes as $breakTime) {
            if ($breakTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $breakTime->break_seconds);
            } else {
                $this->assertEquals(0, $breakTime->break_seconds);
            }
        }
    }

    /**
     * @testdox [労働時間の取得] [前月] 結果の件数が31件である
     */
    public function testAttendancesReturns31ResultsWhenPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $workTimes = $service->attendances()->get();
        $this->assertCount($previousMonth->daysInMonth, $workTimes);
    }

    /**
     * @testdox [労働時間の取得] [当月] 結果の件数が4件である
     */
    public function testAttendancesReturns5ResultsWhenCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $workTimes = $service->attendances()->get();
        $this->assertCount(4, $workTimes);
    }

    /**
     * @testdox [労働時間の取得] [翌月] 結果の件数が 0 件である
     */
    public function testAttendancesReturnsZeroResultsWhenNextMonth(): void
    {
        $nextMonth = $this->today->addMonth();
        $service = new UserAttendanceService($this->user1, $nextMonth);
        $workTimes = $service->attendances()->get();
        $this->assertCount(0, $workTimes);
    }

    /**
     * @testdox [労働時間の取得] [前月] 指定年月に勤務していない場合は 0 秒を返す
     */
    public function testAttendancesReturnsZeroSecondsWhenUserDidNotWorkInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user3, $previousMonth);
        $workTimes = $service->attendances()->get();
        foreach ($workTimes as $workTime) {
            $this->assertEquals(0, $workTime->work_seconds);
        }
    }

    /**
     * @testdox [労働時間の取得] [当日] 指定年月に勤務していない場合は 0 秒を返す
     */
    public function testAttendancesReturnsZeroSecondsWhenUserDidNotWorkInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user3, $this->today);
        $workTimes = $service->attendances()->get();
        foreach ($workTimes as $workTime) {
            $this->assertEquals(0, $workTime->work_seconds);
        }
    }

    /**
     * @testdox [労働時間の取得] [前月] 指定年月に勤務している場合は労働時間を秒数で取得する
     */
    public function testAttendancesReturnsWorkSecondsWhenUserWorkedInPreviousMonth(): void
    {
        $previousMonth = $this->today->subMonth();
        $service = new UserAttendanceService($this->user1, $previousMonth);
        $workTimes = $service->attendances()->get();
        foreach ($workTimes as $workTime) {
            if ($workTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $workTime->work_seconds);
            } else {
                $this->assertEquals(0, $workTime->work_seconds);
            }
        }
    }

    /**
     * @testdox [労働時間の取得] [当月] 指定年月に勤務している場合は労働時間を秒数で取得する
     */
    public function testAttendancesReturnsWorkSecondsWhenUserWorkedInCurrentMonth(): void
    {
        $service = new UserAttendanceService($this->user1, $this->today);
        $workTimes = $service->attendances()->get();
        foreach ($workTimes as $workTime) {
            if ($workTime->date->isWeekday()) {
                $this->assertGreaterThan(0, $workTime->work_seconds);
            } else {
                $this->assertEquals(0, $workTime->work_seconds);
            }
        }
    }
}

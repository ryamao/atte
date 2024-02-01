<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\ShiftBegin;

/**
 * 打刻ページのバックエンドのテストの内、ログイン済み、勤務開始済みのテストケースを扱う。
 */
class StampControllerDuringWorkTest extends StampControllerTestCase
{
    /** テスト開始前に保存された勤務開始イベント */
    private ShiftBegin $shiftBegin;

    /** 勤務開始イベントをデータベースに予め保存しておく。 */
    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftBegin = ShiftBegin::create([
            'user_id' => $this->loginUser->id,
            'begun_at' => $this->testBegunAt,
        ]);
    }

    /**
     * @testdox [GET stamp] [認証状態] [勤務中]
     *
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserDuringWork(): void
    {
        $this->travelTo($this->testBegunAt->addHours(6), fn () => $this->actingAs($this->loginUser)->get(route('stamp')));
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [GET stamp] [認証状態] [勤務中] 日付を跨いだ場合
     *
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserDuringWorkWithPreviousData(): void
    {
        $this->travelTo($this->testBegunAt->addHours(24), fn () => $this->actingAs($this->loginUser)->get(route('stamp')));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務中]
     *
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserDuringWork(): void
    {
        $this->loginAndPost('shift-begin', when: $this->testBegunAt->addHour());
        $this->assertShiftBegins([[$this->loginUser->id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務中] 前日に勤務時間と休憩時間が記録されている場合
     *
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserDuringWorkWithPreviousData(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('shift-begin', when: $this->testBegunAt->addHour());
        $this->assertShiftBegins([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] 日付を跨いだ場合
     *
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserOfCrossDate(): void
    {
        $begunAt = $this->testBegunAt->addHours(24);
        $this->loginAndPost('shift-begin', when: $begunAt);
        $this->assertShiftBegins([[$this->loginUser->id, $begunAt]]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務中]
     *
     * @group stamp
     */
    public function testPostShiftEndFromAuthenticatedUserDuringWork(): void
    {
        $endedAt = $this->testBegunAt->addHours(8);
        $this->loginAndPost('shift-end', when: $endedAt);
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, $endedAt]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務中] 前日に勤務時間と休憩時間が記録されている場合
     *
     * @group stamp
     */
    public function testPostShiftEndFromAuthenticatedUserDuringWorkWithPreviousData(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $endedAt = $this->testBegunAt->addHours(8);
        $this->loginAndPost('shift-end', when: $endedAt);
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([
            $shiftTiming,
            [$this->shiftBegin->user_id, $this->shiftBegin->begun_at, $endedAt],
        ]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] 日付を跨いだ場合
     *
     * @group stamp
     */
    public function testPostShiftEndFromAuthenticatedUserOfCrossDate(): void
    {
        $this->loginAndPost('shift-end', when: $this->testBegunAt->addHours(24));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [勤務中]
     *
     * @group stamp
     */
    public function testPostBreakBeginFromAuthenticatedUserDuringWork(): void
    {
        $begunAt = $this->testBegunAt->addHour();
        $this->loginAndPost('break-begin', when: $begunAt);
        $this->assertShiftBegins([[$this->loginUser->id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([[$this->loginUser->id, $begunAt]]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [勤務中] 前日に勤務時間と休憩時間が記録されている場合
     *
     * @group stamp
     */
    public function testPostBreakBeginFromAuthenticatedUserDuringWorkWithPreviousData(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $begunAt = $this->testBegunAt->addHour();
        $this->loginAndPost('break-begin', when: $begunAt);
        $this->assertShiftBegins([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([[$this->loginUser->id, $begunAt]]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST break-end] [認証状態] [勤務中]
     *
     * @group stamp
     */
    public function testPostBreakEndFromAuthenticatedUserDuringWork(): void
    {
        $this->loginAndPost('break-end', when: $this->testBegunAt->addHour());
        $this->assertShiftBegins([[$this->loginUser->id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-end] [認証状態] [勤務中] 前日に勤務時間と休憩時間が記録されている場合
     *
     * @group stamp
     */
    public function testPostBreakEndFromAuthenticatedUserDuringWorkWithPreviousData(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('break-end', when: $this->testBegunAt->addHour());
        $this->assertShiftBegins([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at]]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }
}

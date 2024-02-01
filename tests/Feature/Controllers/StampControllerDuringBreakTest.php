<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\BreakBegin;
use App\Models\ShiftBegin;

/**
 * 打刻ページのバックエンドのテストの内、ログイン済み、勤務開始済み、休憩開始済みのテストケースを扱う。
 */
class StampControllerDuringBreakTest extends StampControllerTestCase
{
    /** テスト開始前に保存された勤務開始イベント */
    private ShiftBegin $shiftBegin;

    /** テスト開始前に保存された休憩開始イベント */
    private BreakBegin $breakBegin;

    /** 勤務開始イベントと休憩開始イベントをデータベースに予め保存しておく。 */
    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftBegin = ShiftBegin::create([
            'user_id' => $this->loginUser->id,
            'begun_at' => $this->testBegunAt,
        ]);

        $this->breakBegin = BreakBegin::create([
            'user_id' => $this->loginUser->id,
            'begun_at' => $this->testBegunAt->addHours(4),
        ]);
    }

    /**
     * @testdox [GET stamp] [認証状態] [休憩中]
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserDuringBreak(): void
    {
        $this->travelTo($this->testBegunAt->addHours(6), fn () => $this->actingAs($this->loginUser)->get(route('stamp')));
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([$this->breakBegin]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [GET stamp] [認証状態] [休憩中] 日付を跨いだ場合
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserDuringBreakWithPreviousData(): void
    {
        $this->travelTo($this->testBegunAt->addHours(24), fn () => $this->actingAs($this->loginUser)->get(route('stamp')));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->breakBegin->user_id, $this->breakBegin->begun_at, null]]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [休憩中]
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserDuringBreak(): void
    {
        $this->loginAndPost('shift-begin', when: $this->breakBegin->begun_at->addHour());
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([$this->breakBegin]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [休憩中]
     * @group stamp
     */
    public function testPostShiftEndFromAuthenticatedUserDuringBreak(): void
    {
        $this->loginAndPost('shift-end', when: $this->breakBegin->begun_at->addHour());
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([$this->breakBegin]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [休憩中]
     * @group stamp
     */
    public function testPostBreakBeginFromAuthenticatedUserWhileAtBreak(): void
    {
        $this->loginAndPost('break-begin', when: $this->breakBegin->begun_at->addHour());
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([$this->breakBegin]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [休憩中] 日付を跨いだ場合
     * @group stamp
     */
    public function testPostBreakBeginFromAuthenticatedUserDuringBreakWithCrossDate(): void
    {
        $this->loginAndPost('break-begin', when: $this->testBegunAt->addHours(24));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->breakBegin->user_id, $this->breakBegin->begun_at, null]]);
    }

    /**
     * @testdox [POST break-end] [認証状態] [休憩中]
     * @group stamp
     */
    public function testPostBreakEndFromAuthenticatedUserDuringBreak(): void
    {
        $endedAt = $this->breakBegin->begun_at->addHour();
        $this->loginAndPost('break-end', when: $endedAt);
        $this->assertShiftBegins([$this->shiftBegin]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->breakBegin->user_id, $this->breakBegin->begun_at, $endedAt]]);
    }

    /**
     * @testdox [POST break-end] [認証状態] [休憩中] 日付を跨いだ場合
     * @group stamp
     */
    public function testPostBreakEndFromAuthenticatedUserDuringBreakWithCrossDate(): void
    {
        $this->loginAndPost('break-end', when: $this->testBegunAt->addHours(24));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->shiftBegin->user_id, $this->shiftBegin->begun_at, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->breakBegin->user_id, $this->breakBegin->begun_at, null]]);
    }
}

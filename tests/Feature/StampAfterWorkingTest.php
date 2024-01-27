<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShiftTiming;

/**
 * 打刻ページのバックエンドのテストの内、ログイン済み、勤務終了済みのテストケースを扱う。
 */
class StampAfterWorkingTest extends StampTestCase
{
    /** テスト開始前に保存された勤務時間 */
    private ShiftTiming $shiftTiming;

    /** 勤務時間をデータベースに予め保存しておく。 */
    protected function setUp(): void
    {
        parent::setUp();

        $this->shiftTiming = ShiftTiming::create([
            'user_id' => $this->loginUser->id,
            'begun_at' => $this->testBegunAt,
            'ended_at' => $this->testBegunAt->addHours(8),
        ]);
    }

    /**
     * @testdox [GET stamp] [認証状態] [勤務後]
     * @group stamp
     */
    public function test_get_stamp_from_auth_user_after_working(): void
    {
        $this->travelTo($this->testBegunAt->addHours(24), fn () => $this->actingAs($this->loginUser)->get(route('stamp')));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$this->shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務後]
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_user_do_nothing_to_shift_begins_table_after_working(): void
    {
        $this->loginAndPost('shift-begin', when: $this->shiftTiming->ended_at->addHour());
        $this->assertShiftBegins([[$this->loginUser->id, $this->shiftTiming->begun_at]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務後] 前日に勤務時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_user_after_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('shift-begin', when: $this->shiftTiming->ended_at->addHour());
        $this->assertShiftBegins([[$this->loginUser->id, $this->shiftTiming->begun_at]]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務後]
     * @group stamp
     */
    public function test_post_shift_end_from_auth_user_after_working(): void
    {
        $this->loginAndPost('shift-end', when: $this->shiftTiming->ended_at->addHour());
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$this->shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務後] 前日に勤務時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_end_from_auth_user_after_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('shift-end', when: $this->shiftTiming->ended_at->addHour());
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$shiftTiming, $this->shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ShiftBegin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 打刻ページのバックエンドのテストの内、ログイン済み、勤務前のテストケースを扱う。
 */
class StampBeforeWorkingTest extends StampTestCase
{
    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前]
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_user_before_working(): void
    {
        $this->loginAndPost('shift-begin');
        $this->assertShiftBegins([[$this->loginUser->id, $this->testBegunAt]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前] 前日に勤務時間と休憩時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_user_before_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('shift-begin');
        $this->assertShiftBegins([[$this->loginUser->id, $this->testBegunAt]]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前] 複数ユーザによる操作
     * @group stamp
     */
    public function test_post_shift_begin_from_auth_users_before_working(): void
    {
        $anotherUser = User::create([
            'name' => 'test2',
            'email' => 'test2@example.com',
            'password' => Hash::make('password2'),
        ]);
        $shiftBegin = ShiftBegin::create(['user_id' => $anotherUser->id, 'begun_at' => $this->testBegunAt]);

        $this->loginAndPost('shift-begin');
        $this->assertShiftBegins([$shiftBegin, [$this->loginUser->id, $this->testBegunAt]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務前]
     * @group stamp
     */
    public function test_post_shift_end_from_auth_user_before_working(): void
    {
        $this->loginAndPost('shift-end');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-end] [認証状態] [勤務前] 前日に勤務時間と休憩時間が記録されている場合
     * @group stamp
     */
    public function test_post_shift_end_from_auth_user_before_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('shift-end');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [勤務前]
     * @group stamp
     */
    public function test_post_break_begin_from_auth_user_before_working(): void
    {
        $this->loginAndPost('break-begin');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [勤務前] 前日に勤務時間と休憩時間が記録されている場合
     * @group stamp
     */
    public function test_post_break_begin_from_auth_user_before_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('break-begin');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }

    /**
     * @testdox [POST break-end] [認証状態] [勤務前]
     * @group stamp
     */
    public function test_post_break_end_from_auth_user_before_working(): void
    {
        $this->loginAndPost('break-end');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST break-begin] [認証状態] [勤務前] 前日に勤務時間と休憩時間が記録されている場合
     * @group stamp
     */
    public function test_post_break_end_from_auth_user_before_working_with_previous_data(): void
    {
        $shiftTiming = $this->createShiftTiming(begunAt: $this->testBegunAt->subHours(24));
        $breakTiming = $this->createBreakTiming(begunAt: $this->testBegunAt->subHours(20));
        $this->loginAndPost('break-end');
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([$shiftTiming]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([$breakTiming]);
    }
}

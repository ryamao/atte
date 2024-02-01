<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\ShiftBegin;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * 打刻ページのバックエンドのテストの内、ログイン済み、勤務前のテストケースを扱う。
 */
class StampControllerBeforeWorkTest extends StampControllerTestCase
{
    /**
     * @testdox [GET stamp] [認証状態] [勤務前]
     * @group stamp
     */
    public function testGetStampFromAuthenticatedUserBeforeWork(): void
    {
        $this->actingAs($this->loginUser)->get(route('stamp'));
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox [POST shift-begin] [認証状態] [勤務前]
     * @group stamp
     */
    public function testPostShiftBeginFromAuthenticatedUserBeforeWork(): void
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
    public function testPostShiftBeginFromAuthenticatedUserBeforeWorkWithPreviousData(): void
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
    public function testPostShiftBeginFromAuthenticatedUserBeforeWorkWithAnotherUser(): void
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
    public function testPostShiftEndFromAuthenticatedUserBeforeWork(): void
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
    public function testPostShiftEndFromAuthenticatedUserBeforeWorkWithPreviousData(): void
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
    public function testPostBreakBeginFromAuthenticatedUserBeforeWork(): void
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
    public function testPostBreakBeginFromAuthenticatedUserBeforeWorkWithPreviousData(): void
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
    public function testPostBreakEndFromAuthenticatedUserBeforeWork(): void
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
    public function testPostBreakEndFromAuthenticatedUserBeforeWorkWithPreviousData(): void
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

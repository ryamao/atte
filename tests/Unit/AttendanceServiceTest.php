<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    /** テスト実行時に固定する日付 */
    private CarbonImmutable $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDate = CarbonImmutable::create(2024, 1, 23, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($this->testDate);
    }

    /** 勤務情報をアサーションする */
    private function assertAttendance(array $expected, mixed $actual): void
    {
        foreach ($expected as $key => $value) {
            $valueString = var_export($value, true);
            $this->assertSame($value, $actual[$key], "Failed asserting that '$key' is $valueString.");
        }
    }

    /**
     * @testdox 勤怠情報の取得
     * @group attendance
     */
    public function test_getAttendance_normal(): void
    {
        $expectedData = User::factory(100)->create()->map(function (User $user) {
            $shiftTiming = ShiftTiming::factory()->recycle($user)->create();
            $breakTimings = BreakTiming::factory()->count(3)->recycle($user)->create();
            $breakSeconds = $breakTimings->every(fn ($bt) => $bt->ended_at !== null)
                ? $breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->ended_at->diffInSeconds($breakTiming->begun_at))
                : null;
            $shiftSeconds = $shiftTiming->ended_at?->diffInSeconds($shiftTiming->begun_at);

            return [
                'user_id' => $user->id,
                'shift_begun_at' => $shiftTiming->begun_at->toDateTimeString(),
                'shift_ended_at' => $shiftTiming->ended_at?->toDateTimeString(),
                'work_seconds' => $shiftSeconds && $breakSeconds ? strval($shiftSeconds - $breakSeconds) : null,
                'break_seconds' => strval($breakSeconds),
            ];
        });

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount($expectedData->count(), $attendances);

        foreach ($expectedData->zip($attendances) as [$expected, $attendance]) {
            $this->assertAttendance($expected, $attendance);
        }
    }

    /**
     * @testdox 休憩がない場合
     * @group attendance
     */
    public function test_getAttendance_with_no_break(): void
    {
        $shiftTiming = ShiftTiming::factory()->create();

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'user_id' => $shiftTiming->user->id,
                'shift_begun_at' => $shiftTiming->begun_at->toDateTimeString(),
                'shift_ended_at' => $shiftTiming->ended_at->toDateTimeString(),
                'work_seconds' => (string) $shiftTiming->ended_at?->diffInSeconds($shiftTiming->begun_at),
                'break_seconds' => '0',
            ],
            $attendances->first(),
        );
    }

    /**
     * @testdox 勤務終了していないユーザの場合
     * @group attendance
     */
    public function test_getAttendance_with_unended_shift(): void
    {
        $shiftTiming = ShiftTiming::factory()->create(['ended_at' => null]);

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'user_id' => $shiftTiming->user->id,
                'shift_begun_at' => $shiftTiming->begun_at->toDateTimeString(),
                'shift_ended_at' => null,
                'work_seconds' => null,
                'break_seconds' => '0',
            ],
            $attendances->first(),
        );
    }

    /**
     * @testdox 休憩終了していないユーザの場合
     * @group attendance
     */
    public function test_getAttendance_with_unended_break(): void
    {
        $user = User::factory()->create();
        $shiftTiming = ShiftTiming::factory()->recycle($user)->create(['ended_at' => null]);
        BreakTiming::factory()->recycle($user)->create(['ended_at' => null]);

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'user_id' => $user->id,
                'shift_begun_at' => $shiftTiming->begun_at->toDateTimeString(),
                'shift_ended_at' => null,
                'work_seconds' => null,
                'break_seconds' => null,
            ],
            $attendances->first(),
        );
    }

    /**
     * @testdox 勤務中のユーザの場合
     * @group attendance
     */
    public function test_getAttendance_with_working_user(): void
    {
        $shiftBegin = ShiftBegin::factory()->create();

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'user_id' => $shiftBegin->user->id,
                'shift_begun_at' => $shiftBegin->begun_at->toDateTimeString(),
                'shift_ended_at' => null,
                'work_seconds' => null,
                'break_seconds' => '0',
            ],
            $attendances->first(),
        );
    }

    /**
     * @testdox 休憩中のユーザの場合
     * @group attendance
     */
    public function test_getAttendance_with_breaking_user(): void
    {
        $user = User::factory()->create();
        $shiftBegin = ShiftBegin::factory()->recycle($user)->create();
        BreakBegin::factory()->recycle($user)->create();

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'user_id' => $user->id,
                'shift_begun_at' => $shiftBegin->begun_at->toDateTimeString(),
                'shift_ended_at' => null,
                'work_seconds' => null,
                'break_seconds' => null,
            ],
            $attendances->first(),
        );
    }
}

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
    private function assertAttendance(array $expected, array $actual): void
    {
        foreach ($expected as $key => $value) {
            $this->assertEquals($value, $actual[$key]);
        }
    }

    /**
     * @testdox 勤怠情報の取得
     * @group attendance
     */
    public function test_getAttendance(): void
    {
        $expectedData = User::factory(100)->create()->map(function (User $user) {
            $shiftTiming = ShiftTiming::factory()->recycle($user)->create();
            $breakTimings = BreakTiming::factory()->count(3)->recycle($user)->create();
            $breakSeconds = $breakTimings->every(fn ($bt) => $bt->ended_at !== null)
                ? $breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->ended_at->diffInSeconds($breakTiming->begun_at))
                : null;
            $shiftSeconds = $shiftTiming->ended_at?->diffInSeconds($shiftTiming->begun_at);

            return [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => $shiftTiming->ended_at,
                'workSeconds' => $shiftSeconds && $breakSeconds ? $shiftSeconds - $breakSeconds : null,
                'breakSeconds' => $breakSeconds,
            ];
        });

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

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
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $shiftTiming->user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => $shiftTiming->ended_at,
                'workSeconds' => $shiftTiming->ended_at?->diffInSeconds($shiftTiming->begun_at),
                'breakSeconds' => 0,
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
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $shiftTiming->user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => null,
                'workSeconds' => null,
                'breakSeconds' => 0,
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
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => null,
                'workSeconds' => null,
                'breakSeconds' => null,
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
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $shiftBegin->user->name,
                'shiftBegunAt' => $shiftBegin->begun_at,
                'shiftEndedAt' => null,
                'workSeconds' => null,
                'breakSeconds' => 0,
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
        BreakBegin::factory()->create();

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftBegin->begun_at,
                'shiftEndedAt' => null,
                'workSeconds' => null,
                'breakSeconds' => null,
            ],
            $attendances->first(),
        );
    }
}

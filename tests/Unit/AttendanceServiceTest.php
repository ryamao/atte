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

    /** テスト開始時の日付 */
    private CarbonImmutable $testDate;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testDate = CarbonImmutable::create(2024, 1, 23, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($this->testDate);
    }

    /** 勤務時間のテストデータを作成する */
    private function createShiftTiming(User $user, CarbonImmutable $begunAt): ShiftTiming
    {
        $endedAt = $begunAt->addSeconds(fake()->numberBetween(9 * 60 * 60, 10 * 60 * 60));
        return ShiftTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => $endedAt,
        ]);
    }

    /** 勤務終了していない勤務時間のテストデータを作成する */
    private function createUnendedShiftTiming(User $user, CarbonImmutable $begunAt): ShiftTiming
    {
        return ShiftTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => null,
        ]);
    }

    /** 勤務開始イベントのテストデータを作成する */
    private function createShiftBegin(User $user, CarbonImmutable $begunAt): ShiftBegin
    {
        return ShiftBegin::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
        ]);
    }

    /** 休憩時間のテストデータを作成する */
    private function createBreakTiming(User $user, CarbonImmutable $begunAt, int $breakingSeconds): BreakTiming
    {
        $endedAt = $begunAt->addSeconds($breakingSeconds + fake()->numberBetween(0, 10 * 60));
        return BreakTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => $endedAt,
        ]);
    }

    /** 休憩終了していない休憩時間のテストデータを作成する */
    private function createUnendedBreakTiming(User $user, CarbonImmutable $begunAt, int $breakingSeconds): BreakTiming
    {
        return BreakTiming::create([
            'user_id' => $user->id,
            'begun_at' => $begunAt,
            'ended_at' => null,
        ]);
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
        $expectedData = User::factory(6)->create()->map(function (User $user) {
            $shiftTiming = $this->createShiftTiming($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

            $breakingSeconds = 0;
            foreach (range(1, 3) as $i) {
                $breakBegunAt = $shiftTiming->begun_at->addHours(2 * $i)->addSeconds(fake()->numberBetween(0, 30 * 60));
                $breakTiming = $this->createBreakTiming($user, $breakBegunAt, 20 * 60);
                $breakingSeconds += $breakTiming->ended_at->diffInSeconds($breakTiming->begun_at);
            }

            $workingSeconds = $shiftTiming->ended_at->diffInSeconds($shiftTiming->begun_at) - $breakingSeconds;

            return [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => $shiftTiming->ended_at,
                'workingSeconds' => $workingSeconds,
                'breakingSeconds' => $breakingSeconds,
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
        $user = User::factory()->create();

        $shiftTiming = $this->createShiftTiming($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => $shiftTiming->ended_at,
                'workingSeconds' => $shiftTiming->ended_at->diffInSeconds($shiftTiming->begun_at),
                'breakingSeconds' => 0,
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
        $user = User::factory()->create();

        $shiftTiming = $this->createUnendedShiftTiming($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => null,
                'workingSeconds' => null,
                'breakingSeconds' => 0,
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

        $shiftTiming = $this->createUnendedShiftTiming($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

        $breakTiming = $this->createUnendedBreakTiming($user, $shiftTiming->begun_at->addHours(4), 20 * 60);

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftTiming->begun_at,
                'shiftEndedAt' => null,
                'workingSeconds' => null,
                'breakingSeconds' => null,
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
        $user = User::factory()->create();

        $shiftBegin = $this->createShiftBegin($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftBegin->begun_at,
                'shiftEndedAt' => null,
                'workingSeconds' => null,
                'breakingSeconds' => 0,
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

        $shiftBegin = $this->createShiftBegin($user, $this->testDate->addSeconds(fake()->numberBetween(-30 * 60, 0)));

        BreakBegin::create([
            'user_id' => $user->id,
            'begun_at' => $shiftBegin->begun_at->addHours(4),
        ]);

        $service = new AttendanceService($this->testDate);
        $attendances = $service->getAttendances();

        $this->assertCount(1, $attendances);
        $this->assertAttendance(
            [
                'userName' => $user->name,
                'shiftBegunAt' => $shiftBegin->begun_at,
                'shiftEndedAt' => null,
                'workingSeconds' => null,
                'breakingSeconds' => null,
            ],
            $attendances->first(),
        );
    }
}

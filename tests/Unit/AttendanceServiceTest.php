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
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase;

    /** テスト実行時に固定する日付 */
    private CarbonImmutable $today;

    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::create(2024, 1, 23, 9, 0, 0, 'Asia/Tokyo');
        CarbonImmutable::setTestNow($this->today);
    }

    /**
     * @testdox 休憩時間の取得
     * @group attendance
     * @dataProvider provideBreakSecondsTestData
     */
    public function testBreakSeconds(Factory $userFactory): void
    {
        $user = $userFactory->create();
        $service = new AttendanceService($this->today);
        $breakSeconds = $service->breakSeconds()->get();
        $this->assertBreakSeconds(collect([$user]), $breakSeconds);
    }

    /**
     * @testdox 休憩時間の取得 (複数ユーザ)
     * @group attendance
     */
    public function testBreakSecondsWithMixedShifts(): void
    {
        $users = collect(static::provideBreakSecondsTestData())->map(
            fn (array $data) => $data[0]->create()
        );
        $service = new AttendanceService($this->today);
        $breakSeconds = $service->breakSeconds()->get();
        $this->assertBreakSeconds($users, $breakSeconds);
    }

    /** @return array<string, array<Factory<User>>> */
    public static function provideBreakSecondsTestData(): array
    {
        return [
            '勤務前' => [
                User::factory()
            ],
            '休憩0回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
            ],
            '休憩1回(休憩中)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakBegin::factory())
            ],
            '休憩1回(未終了)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],
            '休憩1回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory())
            ],
            '休憩2回(休憩中)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory())
                    ->has(BreakBegin::factory())
            ],
            '休憩2回(未終了)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory())
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],
            '休憩2回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory(2))
            ],
        ];
    }

    /**
     * @testdox 勤務時間の取得
     * @group attendance
     * @dataProvider provideShiftSecondsTestData
     */
    public function testShiftSeconds(Factory $userFactory): void
    {
        $user = $userFactory->create();
        $service = new AttendanceService($this->today);
        $shiftSeconds = $service->shiftSeconds()->get();
        $this->assertShiftSeconds(collect([$user]), $shiftSeconds);
    }

    /**
     * @testdox 勤務時間の取得 (複数ユーザ)
     * @group attendance
     */
    public function testShiftSecondsWithMixedShifts(): void
    {
        $users = collect(static::provideShiftSecondsTestData())->map(
            fn (array $data) => $data[0]->create()
        );
        $service = new AttendanceService($this->today);
        $shiftSeconds = $service->shiftSeconds()->get();
        $this->assertShiftSeconds($users, $shiftSeconds);
    }

    /** @return array<string, array<Factory<User>>> */
    public static function provideShiftSecondsTestData(): array
    {
        return [
            '勤務前' => [
                User::factory()
            ],
            '勤務中' => [
                User::factory()
                    ->has(ShiftBegin::factory())
            ],
            '勤務後' => [
                User::factory()
                    ->has(ShiftTiming::factory())
            ],
            '勤務後(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
            ],
        ];
    }

    /**
     * @testdox 勤務情報の取得
     * @group attendance
     * @dataProvider provideAttendanceTestData
     */
    public function testAttendances(Factory $userFactory): void
    {
        $users = collect([$userFactory->create()]);
        $service = new AttendanceService($this->today);
        $attendances = $service->attendances()->get();
        $this->assertAttendances($users, $attendances);
    }

    /**
     * @testdox 勤務情報の取得 (複数ユーザ)
     * @group attendance
     */
    public function testAttendancesWithMixedShifts(): void
    {
        $users = collect(static::provideAttendanceTestData())->map(
            fn (array $data) => $data[0]->create()
        );
        $service = new AttendanceService($this->today);
        $attendances = $service->attendances()->get();
        $this->assertAttendances($users, $attendances);
    }

    /** @return array<string, array<Factory<User>>> */
    public static function provideAttendanceTestData(): array
    {
        return [
            '勤務前' => [
                User::factory()
            ],
            '勤務中 / 休憩0回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
            ],
            '勤務中 / 休憩1回(休憩中)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakBegin::factory())
            ],
            '勤務中 / 休憩1回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory())
            ],
            '勤務中 / 休憩2回(休憩中)' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory())
                    ->has(BreakBegin::factory())
            ],
            '勤務中 / 休憩2回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory(2))
            ],

            '勤務後 / 休憩0回' => [
                User::factory()
                    ->has(ShiftTiming::factory())
            ],
            '勤務後 / 休憩1回' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakTiming::factory())
            ],
            '勤務後 / 休憩2回' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakTiming::factory(2))
            ],

            '勤務後(未終了) / 休憩0回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
            ],
            '勤務後(未終了) / 休憩1回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],
            '勤務後(未終了) / 休憩2回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
                    ->has(BreakTiming::factory(2))
            ],
            '勤務後(未終了) / 休憩2回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
                    ->has(BreakTiming::factory())
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],

            '異常系 / 勤務前 / 休憩中' => [
                User::factory()
                    ->has(BreakBegin::factory())
            ],
            '異常系 / 勤務前 / 休憩1回(未終了)' => [
                User::factory()
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],
            '異常系 / 勤務後 / 休憩中' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakBegin::factory())
            ],
            '異常系 / 勤務後 / 休憩1回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakTiming::factory()->state(['ended_at' => null]))
            ],
            '異常系 / 勤務後(未終了) / 休憩中' => [
                User::factory()
                    ->has(ShiftTiming::factory()->state(['ended_at' => null]))
                    ->has(BreakBegin::factory())
            ],
        ];
    }

    /**
     * 休憩時間をアサーションする
     *
     * @param Collection<User> $testData
     * @param Collection<array{id: int, break_seconds: string|null}> $actualData
     */
    private function assertBreakSeconds(Collection $testData, Collection $actualData): void
    {
        $this->assertSameSize($testData, $actualData);

        $testData = $testData->sortBy('id');
        $actualData = $actualData->sortBy('user_id');

        foreach ($testData->zip($actualData) as [$user, $breakSeconds]) {
            $this->assertSame($user->id, $breakSeconds['user_id']);

            $expected = $this->sumBreakSeconds($user);
            $actual = $breakSeconds['break_seconds'];
            if (is_null($expected)) {
                $this->assertNull($actual);
            } else {
                $this->assertSame(strval($expected), strval($actual));
            }
        }
    }

    /** 休憩時間の合計を計算する。休憩中の場合は null を返す。 */
    private function sumBreakSeconds(User $user): ?int
    {
        if (isset($user->breakBegin)) return null;
        if ($user->breakTimings->first(fn ($bt) => is_null($bt->ended_at))) return null;
        return $user->breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->timeInSeconds());
    }

    /**
     * 勤務時間をアサーションする
     *
     * @param Collection<User> $testData
     * @param Collection<array{user_id: int, begun_at: string, ended_at: string|null, shift_seconds: int|null}> $actualData
     */
    private function assertShiftSeconds(Collection $testData, Collection $actualData): void
    {
        $testData = $testData->filter(fn (User $user) => $user->shiftBegin || $user->shiftTimings->count() === 1);

        $this->assertSameSize($testData, $actualData);

        $testData = $testData->sortBy('id');
        $actualData = $actualData->sortBy('user_id');

        foreach ($testData->zip($actualData) as [$user, $shiftSeconds]) {
            $this->assertSame($user->id, $shiftSeconds['user_id']);

            $shift = $user->shiftBegin ?? $user->shiftTimings->first();
            $this->assertSame($shift->begun_at, $shiftSeconds['begun_at']);

            if (is_null($shift->ended_at)) {
                $this->assertNull($shiftSeconds['ended_at']);
                $this->assertNull($shiftSeconds['shift_seconds']);
            } else {
                $this->assertSame($shift->ended_at, $shiftSeconds['ended_at']);
                $this->assertSame(strval($shift->timeInSeconds()), strval($shiftSeconds['shift_seconds']));
            }
        }
    }

    /**
     * 勤務情報をアサーションする
     *
     * @param Collection<User> $users
     * @param Collection<array{user_name: string, shift_begun_at: string, shift_ended_at: string|null, work_seconds: string|null, break_seconds: string|null}> $attendances
     */
    private function assertAttendances(Collection $testData, Collection $attendances): void
    {
        $testData = $testData->filter(fn (User $user) => $user->shiftBegin || $user->shiftTimings->count() === 1);

        $this->assertSameSize($testData, $attendances);

        foreach ($testData->sortBy('name')->zip($attendances) as [$user, $attendance]) {
            $shiftBegin = $user->shiftBegin ?? $user->shiftTimings->first();
            $breakSeconds = $this->sumBreakSeconds($user);
            $workSeconds = $this->computeWorkSeconds($user, $breakSeconds);

            $this->assertSame($user->name, $attendance['user_name']);

            $this->assertSame($shiftBegin->begun_at, $attendance['shift_begun_at']);
            $this->assertSame($user->shiftTimings->first()?->ended_at, $attendance['shift_ended_at']);

            if (is_null($workSeconds)) {
                $this->assertNull($attendance['work_seconds']);
            } else {
                $this->assertSame(strval($workSeconds), strval($attendance['work_seconds']));
            }

            if (is_null($breakSeconds)) {
                $this->assertNull($attendance['break_seconds']);
            } else {
                $this->assertSame(strval($breakSeconds), strval($attendance['break_seconds']));
            }
        }
    }

    /** 労働時間を計算する。基本的には『勤務時間 - 休憩時間』だが、勤務中や休憩中、未終了の場合は null を返す。 */
    private function computeWorkSeconds(User $user, ?int $breakSeconds): ?int
    {
        if (isset($user->shiftBegin)) return null;
        if (is_null($breakSeconds)) return null;
        $shiftSeconds = $user->shiftTimings->first()?->timeInSeconds();
        return is_null($shiftSeconds) ? null : $shiftSeconds - $breakSeconds;
    }
}

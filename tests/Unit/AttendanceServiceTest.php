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
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Collection;
use Tests\TestCase;

class AttendanceServiceTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /** テスト実行時に固定する日付 */
    private CarbonImmutable $today;

    /** すべてのテストで共通の日付を使用する */
    protected function setUp(): void
    {
        parent::setUp();

        $this->today = CarbonImmutable::create(2024, 1, 24, 9, 0, 0, 'Asia/Tokyo');
    }

    /**
     * @testdox 休憩時間の取得
     * @group attendance
     * @dataProvider provideBreakSecondsTestData
     */
    public function testBreakSeconds(Factory $userFactory): void
    {
        $user = $this->travelTo($this->today, fn () => $userFactory->create());
        $service = new AttendanceService($this->today);
        $breakSeconds = $service->breakSeconds()->get();
        $this->assertBreakSeconds(collect([$user]), $breakSeconds, $this->today);
    }

    /**
     * @testdox 休憩時間の取得 (複数ユーザ)
     * @group attendance
     */
    public function testBreakSecondsWithMixedShifts(): void
    {
        $users = collect(static::provideBreakSecondsTestData())->map(
            fn (array $data) => $this->travelTo($this->today, fn () => $data[0]->create())
        );
        $service = new AttendanceService($this->today);
        $breakSeconds = $service->breakSeconds()->get();
        $this->assertBreakSeconds($users, $breakSeconds, $this->today);
    }

    /**
     * @testdox 勤務時間の取得
     * @group attendance
     * @dataProvider provideShiftSecondsTestData
     */
    public function testShiftSeconds(Factory $userFactory): void
    {
        $user = $this->travelTo($this->today, fn () => $userFactory->create());
        $service = new AttendanceService($this->today);
        $shiftSeconds = $service->shiftSeconds()->get();
        $this->assertShiftSeconds(collect([$user]), $shiftSeconds, $this->today);
    }

    /**
     * @testdox 勤務時間の取得 (複数ユーザ)
     * @group attendance
     */
    public function testShiftSecondsWithMixedShifts(): void
    {
        $users = collect(static::provideShiftSecondsTestData())->map(
            fn (array $data) => $this->travelTo($this->today, fn () => $data[0]->create())
        );
        $service = new AttendanceService($this->today);
        $shiftSeconds = $service->shiftSeconds()->get();
        $this->assertShiftSeconds($users, $shiftSeconds, $this->today);
    }

    /**
     * @testdox 勤務時間の取得 (複数日)
     * @group attendance
     */
    public function testShiftSecondsWithMultipleDays(): void
    {
        $users = User::factory(100)->create();
        $dates = collect([$this->today, $this->today->subDay()]);

        foreach ($dates as $date) {
            $testData[$date->toDateString()] = $this->travelTo($date, fn () => $users->map(
                fn (User $user) => $this->addRandomAttendance($user, allowBegin: $date->isSameDay($this->today))
            ));
        }

        foreach ($dates as $date) {
            $expectedResult = $testData[$date->toDateString()]->sortBy('user_id');

            $service = new AttendanceService($date);
            $shiftSeconds = $service->shiftSeconds()->orderBy('user_id')->get();

            $this->assertSameSize($expectedResult, $shiftSeconds);
            foreach ($expectedResult->zip($shiftSeconds) as [$expected, $actual]) {
                $dump = "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($actual->toArray(), true);
                $this->assertSame($expected['user_id'], $actual['user_id'], "{$date->toDateString()} {$dump}");
                $this->assertSame($expected['shift_begun_at'], $actual['shift_begun_at'], "{$date->toDateString()} {$dump}");
                $this->assertSame($expected['shift_ended_at'], $actual['shift_ended_at'], "{$date->toDateString()} {$dump}");
                $this->assertSameSeconds($expected['shift_seconds'], $actual['shift_seconds'], "{$date->toDateString()} {$dump}");
            }
        }
    }

    /**
     * @testdox 勤務情報の取得
     * @group attendance
     * @dataProvider provideAttendanceTestData
     */
    public function testAttendances(Factory $userFactory): void
    {
        $user = $this->travelTo($this->today, fn () => $userFactory->create());
        $service = new AttendanceService($this->today);
        $attendances = $service->attendances()->get();
        $this->assertAttendances(collect([$user]), $attendances, $this->today);
    }

    /**
     * @testdox 勤務情報の取得 (複数ユーザ)
     * @group attendance
     */
    public function testAttendancesWithMixedShifts(): void
    {
        $users = collect(static::provideAttendanceTestData())->map(
            fn (array $data) => $this->travelTo($this->today, fn () => $data[0]->create())
        );
        $service = new AttendanceService($this->today);
        $attendances = $service->attendances()->get();
        $this->assertAttendances($users, $attendances, $this->today);
    }

    /**
     * @testdox 勤務情報の取得 (複数日)
     * @group attendance
     */
    public function testAttendancesWithMultipleDays(): void
    {
        $users = User::factory(100)->create();
        $dates = collect([$this->today, $this->today->subDay()]);

        foreach ($dates as $date) {
            $testData[$date->toDateString()] = $this->travelTo($date, fn () => $users->map(
                fn (User $user) => $this->addRandomAttendance($user, allowBegin: $date->isSameDay($this->today))
            ));
        }

        foreach ($dates as $date) {
            $expected = $testData[$date->toDateString()]->sortBy('user_id');

            $service = new AttendanceService($date);
            $attendances = $service->attendances()->orderBy('user_id')->get();

            $this->assertSameSize($expected, $attendances);
            foreach ($expected->zip($attendances) as [$expected, $attendance]) {
                $dump = "\nexpected: " . var_export($expected, true) . "\nactual: " . var_export($attendance->toArray(), true);
                $this->assertSame($expected['user_id'], $attendance['user_id'], "{$date->toDateString()} {$dump}");
                $this->assertSame($expected['shift_begun_at'], $attendance['shift_begun_at'], "{$date->toDateString()} {$dump}");
                $this->assertSame($expected['shift_ended_at'], $attendance['shift_ended_at'], "{$date->toDateString()} {$dump}");
                $this->assertSameSeconds($expected['work_seconds'], $attendance['work_seconds'], "{$date->toDateString()} {$dump}");
                $this->assertSameSeconds($expected['break_seconds'], $attendance['break_seconds'], "{$date->toDateString()} {$dump}");
            }
        }
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
                    ->has(BreakTiming::factory()->unended())
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
                    ->has(BreakTiming::factory()->unended())
            ],
            '休憩2回' => [
                User::factory()
                    ->has(ShiftBegin::factory())
                    ->has(BreakTiming::factory(2))
            ],
        ];
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
                    ->has(ShiftTiming::factory()->unended())
            ],
        ];
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
                    ->has(ShiftTiming::factory()->unended())
            ],
            '勤務後(未終了) / 休憩1回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory()->unended())
                    ->has(BreakTiming::factory()->unended())
            ],
            '勤務後(未終了) / 休憩2回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->unended())
                    ->has(BreakTiming::factory(2))
            ],
            '勤務後(未終了) / 休憩2回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory()->unended())
                    ->has(BreakTiming::factory())
                    ->has(BreakTiming::factory()->unended())
            ],

            '異常系 / 勤務前 / 休憩中' => [
                User::factory()
                    ->has(BreakBegin::factory())
            ],
            '異常系 / 勤務前 / 休憩1回(未終了)' => [
                User::factory()
                    ->has(BreakTiming::factory()->unended())
            ],
            '異常系 / 勤務後 / 休憩中' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakBegin::factory())
            ],
            '異常系 / 勤務後 / 休憩1回(未終了)' => [
                User::factory()
                    ->has(ShiftTiming::factory())
                    ->has(BreakTiming::factory()->unended())
            ],
            '異常系 / 勤務後(未終了) / 休憩中' => [
                User::factory()
                    ->has(ShiftTiming::factory()->unended())
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
    private function assertBreakSeconds(Collection $testData, Collection $actualData, CarbonImmutable $date): void
    {
        $this->assertSameSize($testData, $actualData);

        $testData = $testData->sortBy('id');
        $actualData = $actualData->sortBy('user_id');

        foreach ($testData->zip($actualData) as [$user, $actual]) {
            $this->assertSame($user->id, $actual['user_id']);
            $this->assertSameSeconds($user->breakTimeInSeconds($date), $actual['break_seconds']);
        }
    }

    /**
     * 勤務時間をアサーションする
     *
     * @param Collection<User> $testData
     * @param Collection<array{user_id: int, begun_at: string, ended_at: string|null, shift_seconds: int|null}> $actualData
     */
    private function assertShiftSeconds(Collection $testData, Collection $actualData): void
    {
        $this->assertSameSize($testData, $actualData);

        $testData = $testData->sortBy('id');
        $actualData = $actualData->sortBy('user_id');

        foreach ($testData->zip($actualData) as [$user, $shiftSeconds]) {
            $this->assertSame($user->id, $shiftSeconds['user_id']);
            $this->assertSame($user->shiftBegunAt($this->today)?->toDateTimeString(), $shiftSeconds['shift_begun_at']);
            $this->assertSame($user->shiftEndedAt($this->today)?->toDateTimeString(), $shiftSeconds['shift_ended_at']);
            $this->assertSame($user->shiftTimeInSeconds($this->today), $shiftSeconds['shift_seconds']);
        }
    }

    /**
     * 勤務情報をアサーションする
     *
     * @param Collection<User> $users
     * @param Collection<array{user_name: string, shift_begun_at: string, shift_ended_at: string|null, work_seconds: string|null, break_seconds: string|null}> $attendances
     */
    private function assertAttendances(Collection $testData, Collection $attendances, CarbonImmutable $date): void
    {
        $this->assertSameSize($testData, $attendances);

        $testData = $testData->sortBy('id');
        $attendances = $attendances->sortBy('user_id');

        foreach ($testData->zip($attendances) as [$user, $attendance]) {
            $message = 'actual: ' . var_export($attendance->toArray(), true);

            $this->assertSame($user->id, $attendance['user_id'], $message);
            $this->assertSame($user->name, $attendance['user_name'], $message);

            $this->assertSame($user->shiftBegunAt($date)?->toDateTimeString(), $attendance['shift_begun_at'], $message);
            $this->assertSame($user->shiftEndedAt($date)?->toDateTimeString(), $attendance['shift_ended_at'], $message);

            $this->assertSameSeconds($user->breakTimeInSeconds($date), $attendance['break_seconds'], $message);
            $this->assertSameSeconds($user->workTimeInSeconds($date), $attendance['work_seconds'], $message);
        }
    }

    /** データベースから返ってきた秒数をアサーションする */
    private function assertSameSeconds(?int $expected, int|string|null $actual, string $message = ''): void
    {
        if (is_null($expected)) {
            $this->assertNull($actual, $message);
        } else if (is_integer($actual)) {
            $this->assertSame($expected, $actual, $message);
        } else {
            $this->assertSame((string) $expected, $actual, $message);
        }
    }

    /** 2つの日付が同じ日かどうかを判定する。 */
    private function isSameDay(mixed $date1, mixed $date2): bool
    {
        if (is_null($date1) || is_null($date2)) return false;
        return CarbonImmutable::make($date1)->isSameDay(CarbonImmutable::make($date2));
    }

    /** ユーザにランダムな勤怠情報を付与する。 */
    private function addRandomAttendance(User $user, bool $allowBegin = false): array
    {
        $data['user_id'] = $user->id;

        if ($this->faker->boolean(5)) {
            $data['shift_begun_at'] = null;
            $data['shift_ended_at'] = null;
            $data['shift_seconds'] = 0;
            $data['work_seconds'] = 0;
            $data['break_seconds'] = 0;
        } else {
            $countBreaks = $this->faker->numberBetween(0, 3);
            if ($countBreaks >= 1) {
                if ($this->faker->boolean(90)) {
                    $breakTimings = BreakTiming::factory($countBreaks)->recycle($user)->create();
                    $data['break_seconds'] = $breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->timeInSeconds());
                } else {
                    if ($countBreaks >= 2) BreakTiming::factory($countBreaks - 1)->recycle($user)->create();
                    BreakTiming::factory()->recycle($user)->unended()->create();
                    $data['break_seconds'] = null;
                }
            } else {
                $data['break_seconds'] = 0;
            }

            if ($allowBegin && $this->faker->boolean(10)) {
                $shiftBegin = ShiftBegin::factory()->recycle($user)->create();

                if ($this->faker->boolean()) {
                    BreakBegin::factory()->recycle($user)->create();
                    $data['break_seconds'] = null;
                }

                $data['shift_begun_at'] = $shiftBegin->begun_at->toDateTimeString();
                $data['shift_ended_at'] = null;
                $data['shift_seconds'] = null;
                $data['work_seconds'] = null;
            } else {
                if ($this->faker->boolean(90)) {
                    $shiftTiming = ShiftTiming::factory()->recycle($user)->create();
                    $data['shift_seconds'] = $shiftTiming->timeInSeconds();
                    $data['work_seconds'] = is_null($data['break_seconds']) ? null : $data['shift_seconds'] - $data['break_seconds'];
                } else {
                    $shiftTiming = ShiftTiming::factory()->recycle($user)->unended()->create();
                    $data['shift_seconds'] = null;
                    $data['work_seconds'] = null;
                }

                $data['shift_begun_at'] = $shiftTiming->begun_at->toDateTimeString();
                $data['shift_ended_at'] = $shiftTiming->ended_at?->toDateTimeString();
            }
        }

        return $data;
    }
}

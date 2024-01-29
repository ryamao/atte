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

    /** 休憩時間の合計を計算する。休憩中の場合は null を返す。 */
    private function sumBreakSeconds(User $user): ?int
    {
        if (isset($user->breakBegin)) return null;
        if ($user->breakTimings->first(fn ($bt) => is_null($bt->ended_at))) return null;
        return $user->breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->timeInSeconds());
    }

    /** 労働時間を計算する。基本的には『勤務時間 - 休憩時間』だが、勤務中や休憩中、未終了の場合は null を返す。 */
    private function computeWorkSeconds(User $user, ?int $breakSeconds): ?int
    {
        if (isset($user->shiftBegin)) return null;
        if (is_null($breakSeconds)) return null;
        $shiftSeconds = $user->shiftTimings->first()->timeInSeconds();
        return is_null($shiftSeconds) ? null : $shiftSeconds - $breakSeconds;
    }

    /**
     * @testdox 勤務情報の取得
     * @group attendance
     * @dataProvider provideAttendances
     */
    public function testAttendances(Factory $userFactory): void
    {
        $users = Collection::wrap($userFactory->create());
        $expectedData = $users->sortBy('name')->map(function (User $user) {
            $shiftBegin = $user->shiftBegin ?? $user->shiftTimings->first();
            $breakSeconds = $this->sumBreakSeconds($user);
            $workSeconds = $this->computeWorkSeconds($user, $breakSeconds);
            return [
                'user_name' => $user->name,
                'shift_begun_at' => $shiftBegin->begun_at,
                'shift_ended_at' => $user->shiftTimings->first()?->ended_at,
                'work_seconds' => is_null($workSeconds) ? null : strval($workSeconds),
                'break_seconds' => is_null($breakSeconds) ? null : strval($breakSeconds),
            ];
        });

        $service = new AttendanceService($this->testDate);
        $attendances = $service->attendances()->get();

        $this->assertCount($expectedData->count(), $attendances);

        foreach ($expectedData->zip($attendances) as [$expected, $attendance]) {
            $this->assertAttendance($expected, $attendance);
        }
    }

    /** @return array<string, array<Factory<User>>> */
    public static function provideAttendances(): array
    {
        return [
            '会員1名 / 勤務後 / 休憩0回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1))
            ],
            '会員1名 / 勤務後 / 休憩1回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1))
            ],
            '会員1名 / 勤務後 / 休憩2回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(2))
            ],
            '会員1名 / 勤務後、未終了 / 休憩2回' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1)->state(['ended_at' => null]))
                    ->has(BreakTiming::factory()->count(2))
            ],
            '会員1名 / 勤務後、未終了 / 休憩2回、2回目未終了' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1)->state(['ended_at' => null]))
                    ->has(BreakTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1)->state(['ended_at' => null]))
            ],
            '会員1名 / 勤務後 / 休憩2回、2回目未終了（異常系）' => [
                User::factory()
                    ->has(ShiftTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1)->state(['ended_at' => null]))
            ],
            '会員1名 / 勤務中 / 休憩1回' => [
                User::factory()
                    ->has(ShiftBegin::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1))
            ],
            '会員1名 / 勤務中 / 休憩2回、2回目休憩中' => [
                User::factory()
                    ->has(ShiftBegin::factory()->count(1))
                    ->has(BreakTiming::factory()->count(1))
                    ->has(BreakBegin::factory()->count(1))
            ],
            '会員2名 / 勤務後 / 休憩2回' => [
                User::factory()
                    ->count(2)
                    ->has(ShiftTiming::factory()->count(1))
                    ->has(BreakTiming::factory()->count(2))
            ],
        ];
    }
}

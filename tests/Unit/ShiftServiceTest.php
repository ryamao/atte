<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\ShiftService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ShiftServiceTest extends TestCase
{
    use RefreshDatabase;

    private array $users;
    private CarbonImmutable $testBegin;

    /** 各テストケース前に実行する */
    protected function setUp(): void
    {
        parent::setUp();

        foreach (range(0, 1) as $i) {
            $this->users[$i] = User::create([
                'name' => "test{$i}",
                'email' => "test{$i}@example.com",
                'password' => Hash::make("password{$i}"),
            ]);
        }

        $this->testBegin = CarbonImmutable::create(2024, 1, 24, 9, 0, 0);
    }

    private function beginShift(int $id = 0): void
    {
        $now = CarbonImmutable::now();
        $shiftService = new ShiftService($this->users[$id], $now);
        $shiftService->beginShift();
    }

    private function endShift(int $id = 0): void
    {
        $now = CarbonImmutable::now();
        $shiftService = new ShiftService($this->users[$id], $now);
        $shiftService->endShift();
    }

    private function assertShiftBegins(array $begins): void
    {
        $this->assertDatabaseCount('shift_begins', count($begins));
        foreach ($begins as [$user, $begun_at]) {
            $user_id = $user->id;
            $this->assertDatabaseHas('shift_begins', compact('user_id', 'begun_at'));
        }
    }

    private function assertShiftTimings(array $timings): void
    {
        $this->assertDatabaseCount('shift_timings', count($timings));
        foreach ($timings as [$user, $begun_at, $ended_at]) {
            $user_id = $user->id;
            $this->assertDatabaseHas('shift_timings', compact('user_id', 'begun_at', 'ended_at'));
        }
    }

    /**
     * @testdox 複数回連続で ShiftService::beginShift を実行しても最初の日時を保持する。
     * @group stamp
     */
    public function test_beginShift(): void
    {
        $this->travelTo($this->testBegin, fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->travelTo($this->testBegin->addHour(), fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->travelTo($this->testBegin->addHours(2), fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
    }

    /**
     * @testdox 日付を跨いで2回 ShiftService::beginShift を実行すると、前日の記録を勤務終了して当日の記録を開始する。
     * @group stamp
     */
    public function test_beginShift_2(): void
    {
        $this->travelTo($this->testBegin, fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->travelTo($this->testBegin->addDay(), fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin->addDay()]]);
        $this->assertShiftTimings([[$this->users[0], $this->testBegin, null]]);
    }

    /**
     * @testdox 9時に勤務開始、17時に勤務終了、18時に勤務再開、19時に勤務終了
     * @group stamp
     */
    public function test_endShift(): void
    {
        $this->travelTo($this->testBegin, fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->travelTo($this->testBegin->addHours(8), fn () => $this->endShift());
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0], $this->testBegin, $this->testBegin->addHours(8)]]);
        $this->travelTo($this->testBegin->addHours(9), fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->assertShiftTimings([]);
        $this->travelTo($this->testBegin->addHours(10), fn () => $this->endShift());
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0], $this->testBegin, $this->testBegin->addHours(10)]]);
    }

    /**
     * @testdox 20時に勤務開始、翌5時に勤務終了
     * @group stamp
     */
    public function test_endShift_2(): void
    {
        $this->testBegin = CarbonImmutable::create(2024, 1, 24, 20, 0, 0);
        $this->travelTo($this->testBegin, fn () => $this->beginShift());
        $this->assertShiftBegins([[$this->users[0], $this->testBegin]]);
        $this->travelTo($this->testBegin->addHours(8), fn () => $this->endShift());
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0], $this->testBegin, null]]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\StampService;
use App\WorkStatus;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\AssertsDatabase;
use Tests\TestCase;

class StampServiceTest extends TestCase
{
    use AssertsDatabase;
    use RefreshDatabase;

    /** 打刻を行うユーザ。 */
    protected array $users;

    /** この日時を基準にして相対時刻でテストする。 */
    protected CarbonImmutable $testBegunAt;

    /** ユーザと基準時刻を用意する。 */
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

        $this->testBegunAt = CarbonImmutable::create(2024, 1, 24, 9, 0, 0, new DateTimeZone('Asia/Tokyo'));
    }

    /** ユーザIDと基準時刻からの経過時間(hours)を指定して StampService を作成する。 */
    private function stamper(int $id = 0, int $elapsedHours = 0): StampService
    {
        return new StampService($this->users[$id], $this->testBegunAt->addHours($elapsedHours));
    }

    /**
     * @testdox ある日の勤務状況を取得する
     *
     * @group model
     */
    public function testWorkStatus(): void
    {
        $user = User::factory()->create();
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');
        $service = new StampService($user, $date);

        $this->assertSame(WorkStatus::Before, $service->workStatus());

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertSame(WorkStatus::During, $service->workStatus());

        $user->breakBegin()->create(['begun_at' => '2021-01-01 12:00:00']);
        $this->assertSame(WorkStatus::Break, $service->workStatus());

        $user->breakBegin()->delete();
        $user->breakTimings()->create(['begun_at' => '2021-01-01 12:00:00']);
        $this->assertSame(WorkStatus::During, $service->workStatus());

        $user->shiftBegin()->delete();
        $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00', 'ended_at' => '2021-01-01 18:00:00']);
        $this->assertSame(WorkStatus::Before, $service->workStatus());
    }

    /**
     * @testdox 勤務開始後に StampService::beginShift を実行しても最初の日時を保持する
     *
     * @group stamp
     */
    public function testBeginShiftTwice(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
        $this->stamper(elapsedHours: 1)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
    }

    /**
     * @testdox 日付を跨いで StampService::beginShift を実行すると、前日の記録を勤務終了して当日の記録を開始する
     *
     * @group stamp
     */
    public function testBeginShiftCrossingDate(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 24)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt->addHours(24)]]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), null]]);
    }

    /**
     * @testdox 休憩後の StampService::beginShift
     *
     * @group stamp
     */
    public function testBeginShiftAfterBreak(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 5)->endBreak();
        $this->stamper(elapsedHours: 6)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt->addHours(0)]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), $this->testBegunAt->addHours(5)]]);
    }

    /**
     * @testdox 勤務終了と勤務再開
     *
     * @group stamp
     */
    public function testEndShiftAndBeginShift(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
        $this->stamper(elapsedHours: 8)->endShift();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, $this->testBegunAt->addHours(8)]]);
        $this->stamper(elapsedHours: 9)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
        $this->assertShiftTimings([]);
        $this->stamper(elapsedHours: 10)->endShift();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, $this->testBegunAt->addHours(10)]]);
    }

    /**
     * @testdox 前日に勤務終了と休憩終了していない状態で StampService::endShift
     *
     * @group stamp
     */
    public function testEndShiftWithPreviousData(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 24)->endShift();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), null]]);
    }

    /**
     * @testdox 休憩中の StampService::endShift
     *
     * @group stamp
     */
    public function testEndShiftWhileAtBreak(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 8)->endShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
        $this->assertShiftTimings([]);
        $this->assertBreakBegins([[$this->users[0]->id, $this->testBegunAt->addHours(4)]]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox 休憩後の StampService::endShift
     *
     * @group stamp
     */
    public function testEndShiftAfterBreak(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 5)->endBreak();
        $this->stamper(elapsedHours: 8)->endShift();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt->addHours(0), $this->testBegunAt->addHours(8)]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), $this->testBegunAt->addHours(5)]]);
    }

    /**
     * @testdox 勤務開始前と勤務終了後では StampService::beginBreak は何もしない
     *
     * @group stamp
     */
    public function testBeginBreakDoNothing(): void
    {
        // 勤務開始前
        $this->stamper(elapsedHours: 0)->beginBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);

        // 勤務終了後
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 8)->endShift();
        $this->stamper(elapsedHours: 9)->beginBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox 休憩開始後に StampService::beginBreak を実行しても最初の日時を保持する
     *
     * @group stamp
     */
    public function testBeginBreakTwice(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->assertBreakBegins([[$this->users[0]->id, $this->testBegunAt->addHours(4)]]);
        $this->stamper(elapsedHours: 5)->beginBreak();
        $this->assertBreakBegins([[$this->users[0]->id, $this->testBegunAt->addHours(4)]]);
    }

    /**
     * @testdox 前日に勤務終了と休憩終了していない状態で StampService::beginBreak
     *
     * @group stamp
     */
    public function testBeginBreakWithPreviousData(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 24)->beginBreak();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), null]]);
    }

    /**
     * @testdox StampService::endBreak のよくある使用例
     *
     * @group stamp
     */
    public function testEndBreak(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 5)->endBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), $this->testBegunAt->addHours(5)]]);
        $this->stamper(elapsedHours: 6)->beginBreak();
        $this->stamper(elapsedHours: 7)->endBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([
            [$this->users[0]->id, $this->testBegunAt->addHours(4), $this->testBegunAt->addHours(5)],
            [$this->users[0]->id, $this->testBegunAt->addHours(6), $this->testBegunAt->addHours(7)],
        ]);
    }

    /**
     * @testdox 勤務開始前と勤務終了後では StampService::endBreak は何もしない
     *
     * @group stamp
     */
    public function testEndBreakDoNothing(): void
    {
        // 勤務開始前
        $this->stamper(elapsedHours: 0)->endBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);

        // 勤務終了後
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 8)->endShift();
        $this->stamper(elapsedHours: 9)->endBreak();
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([]);
    }

    /**
     * @testdox 前日に勤務終了と休憩終了していない状態で StampService::endBreak
     *
     * @group stamp
     */
    public function testEndBreakWithPreviousData(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->stamper(elapsedHours: 24)->endBreak();
        $this->assertShiftBegins([]);
        $this->assertShiftTimings([[$this->users[0]->id, $this->testBegunAt, null]]);
        $this->assertBreakBegins([]);
        $this->assertBreakTimings([[$this->users[0]->id, $this->testBegunAt->addHours(4), null]]);
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\User;
use App\Services\StampService;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\AssertsDatabase;
use Tests\TestCase;

class StampServiceTest extends TestCase
{
    use RefreshDatabase;
    use AssertsDatabase;

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
     * @testdox 勤務開始後に StampService::beginShift を実行しても最初の日時を保持する
     * @group stamp
     */
    public function test_beginShift_twice(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
        $this->stamper(elapsedHours: 1)->beginShift();
        $this->assertShiftBegins([[$this->users[0]->id, $this->testBegunAt]]);
    }

    /**
     * @testdox 日付を跨いで StampService::beginShift を実行すると、前日の記録を勤務終了して当日の記録を開始する
     * @group stamp
     */
    public function test_beginShift_with_previous_data(): void
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
     * @group stamp
     */
    public function test_beginShift_before_breaking(): void
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
     * @testdox 9時に勤務開始、17時に勤務終了、18時に勤務再開、19時に勤務終了
     * @group stamp
     */
    public function test_endShift(): void
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
     * @group stamp
     */
    public function test_endShift_with_previous_data(): void
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
     * @group stamp
     */
    public function test_endShift_while_at_break(): void
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
     * @group stamp
     */
    public function test_endShift_before_breaking(): void
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
     * @group stamp
     */
    public function test_beginBreak_do_nothing(): void
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
     * @group stamp
     */
    public function test_beginBreak_twice(): void
    {
        $this->stamper(elapsedHours: 0)->beginShift();
        $this->stamper(elapsedHours: 4)->beginBreak();
        $this->assertBreakBegins([[$this->users[0]->id, $this->testBegunAt->addHours(4)]]);
        $this->stamper(elapsedHours: 5)->beginBreak();
        $this->assertBreakBegins([[$this->users[0]->id, $this->testBegunAt->addHours(4)]]);
    }

    /**
     * @testdox 前日に勤務終了と休憩終了していない状態で StampService::beginBreak
     * @group stamp
     */
    public function test_beginBreak_with_previous_data(): void
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
     * @group stamp
     */
    public function test_endBreak(): void
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
     * @group stamp
     */
    public function test_endBreak_do_nothing(): void
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
     * @group stamp
     */
    public function test_endBreak_with_previous_data(): void
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

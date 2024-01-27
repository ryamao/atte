<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BreakBegin;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;
use App\WorkStatus;

class WorkStatusTest extends TestCase
{
    use RefreshDatabase;

    /** 打刻を行うユーザ。 */
    protected User $user;

    /** この日時を基準にして相対時刻でテストする。 */
    protected CarbonImmutable $now;

    /** ユーザと基準時刻を用意する。 */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => "test",
            'email' => "test@example.com",
            'password' => Hash::make("password"),
        ]);

        $this->now = CarbonImmutable::create(2024, 1, 24, 9, 0, 0, new DateTimeZone('Asia/Tokyo'));
    }

    /**
     * @testdox 勤務前の WorkStatus::ask
     * @group stamp
     */
    public function test_ask_before_work(): void
    {
        $this->assertSame(WorkStatus::Before, WorkStatus::ask($this->user, $this->now));
    }

    /**
     * @testdox 勤務中の WorkStatus::ask
     * @group stamp
     */
    public function test_ask_during_work(): void
    {
        ShiftBegin::create(['user_id' => $this->user->id, 'begun_at' => $this->now]);
        $this->assertSame(WorkStatus::During, WorkStatus::ask($this->user, $this->now->addHours(3)));
    }

    /**
     * @testdox 勤務後の WorkStatus::ask
     * @group stamp
     */
    public function test_ask_after_work(): void
    {
        ShiftTiming::create(['user_id' => $this->user->id, 'begun_at' => $this->now, 'ended_at' => $this->now->addHours(8)]);
        $this->assertSame(WorkStatus::Before, WorkStatus::ask($this->user, $this->now->addHours(9)));
    }

    /**
     * @testdox 休憩中の WorkStatus::ask
     * @group stamp
     */
    public function test_ask_during_break(): void
    {
        ShiftBegin::create(['user_id' => $this->user->id, 'begun_at' => $this->now]);
        BreakBegin::create(['user_id' => $this->user->id, 'begun_at' => $this->now->addHours(3)]);
        $this->assertSame(WorkStatus::Break, WorkStatus::ask($this->user, $this->now->addHours(4)));
    }
}

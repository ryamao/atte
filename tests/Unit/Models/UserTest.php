<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @testdox ある日に勤務しているかどうかを判定する
     *
     * @group model
     */
    public function testIsWorkingOn(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertFalse($user->isWorkingOn($date));

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertTrue($user->isWorkingOn($date));

        $user->shiftBegin()->delete();
        $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertTrue($user->isWorkingOn($date));
    }

    /**
     * @testdox ある日の勤務開始日時を取得する
     *
     * @group model
     */
    public function testShiftBegunAtDate(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertNull($user->shiftBegunAtDate($date));

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertEquals('2021-01-01 10:00:00', $user->shiftBegunAtDate($date)?->format('Y-m-d H:i:s'));

        $user->shiftBegin()->delete();
        $shiftTiming = $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertEquals('2021-01-01 10:00:00', $user->shiftBegunAtDate($date)?->format('Y-m-d H:i:s'));

        $shiftTiming->update(['ended_at' => '2021-01-01 18:00:00']);
        $this->assertEquals('2021-01-01 10:00:00', $user->shiftBegunAtDate($date)?->format('Y-m-d H:i:s'));
    }

    /**
     * @testdox ある日の勤務終了日時を取得する
     *
     * @group model
     */
    public function testShiftEndedAtDate(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertNull($user->shiftEndedAtDate($date));

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->shiftEndedAtDate($date));

        $user->shiftBegin()->delete();
        $shiftTiming = $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->shiftEndedAtDate($date));

        $shiftTiming->update(['ended_at' => '2021-01-01 18:00:00']);
        $this->assertEquals('2021-01-01 18:00:00', $user->shiftEndedAtDate($date)?->format('Y-m-d H:i:s'));
    }

    /**
     * @testdox ある日の休憩時間を秒数で取得する
     *
     * @group model
     */
    public function testBreakTimeInSeconds(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertEquals(0, $user->breakTimeInSeconds($date));

        $user->breakBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->breakTimeInSeconds($date));

        $user->breakBegin()->delete();
        $breakTiming = $user->breakTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->breakTimeInSeconds($date));

        $breakTiming->update(['ended_at' => '2021-01-01 11:00:00']);
        $this->assertEquals(60 * 60, $user->breakTimeInSeconds($date));
    }

    /**
     * @testdox ある日の勤務時間を秒数で取得する
     *
     * @group model
     */
    public function testShiftTimeInSeconds(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertEquals(0, $user->shiftTimeInSeconds($date));

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->shiftTimeInSeconds($date));

        $user->shiftBegin()->delete();
        $shiftTiming = $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->shiftTimeInSeconds($date));

        $shiftTiming->update(['ended_at' => '2021-01-01 18:00:00']);
        $this->assertEquals(60 * 60 * 8, $user->shiftTimeInSeconds($date));
    }

    /**
     * @testdox ある日の労働時間を秒数で取得する
     *
     * @group model
     */
    public function testWorkTimeInSeconds(): void
    {
        $date = CarbonImmutable::create(year: 2021, month: 1, day: 1, tz: 'Asia/Tokyo');

        $user = User::factory()->create();
        $this->assertEquals(0, $user->workTimeInSeconds($date));

        $user->shiftBegin()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->workTimeInSeconds($date));

        $user->shiftBegin()->delete();
        $shiftTiming = $user->shiftTimings()->create(['begun_at' => '2021-01-01 10:00:00']);
        $this->assertNull($user->workTimeInSeconds($date));

        $shiftTiming->update(['ended_at' => '2021-01-01 18:00:00']);
        $this->assertEquals(60 * 60 * 8, $user->workTimeInSeconds($date));

        $user->breakBegin()->create(['begun_at' => '2021-01-01 12:00:00']);
        $this->assertNull($user->workTimeInSeconds($date));

        $user->breakBegin()->delete();
        $breakTiming = $user->breakTimings()->create(['begun_at' => '2021-01-01 12:00:00']);
        $this->assertNull($user->workTimeInSeconds($date));

        $breakTiming->update(['ended_at' => '2021-01-01 13:00:00']);
        $this->assertEquals(60 * 60 * 7, $user->workTimeInSeconds($date));
    }
}

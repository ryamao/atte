<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\ShiftTiming;
use Tests\TestCase;

class ShiftTimingTest extends TestCase
{
    /**
     * @testdox timeInSeconds() は勤務時間を秒数で返す。
     *
     * @group model
     */
    public function testTimeInSeconds()
    {
        $shiftTiming = new ShiftTiming([
            'begun_at' => '2021-01-01 09:00:00',
            'ended_at' => '2021-01-01 18:00:00',
        ]);

        $this->assertEquals(9 * 60 * 60, $shiftTiming->timeInSeconds());
    }

    /**
     * @testdox timeInSeconds() は勤務終了せずに日付を跨いだ場合の勤務時間では null を返す。
     *
     * @group model
     */
    public function testTimeInSecondsReturnsNullWhenShiftCrossesDate()
    {
        $shiftTiming = new ShiftTiming([
            'begun_at' => '2021-01-01 09:00:00',
            'ended_at' => null,
        ]);

        $this->assertNull($shiftTiming->timeInSeconds());
    }
}

<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\BreakTiming;
use Tests\TestCase;

class BreakTimingTest extends TestCase
{
    /**
     * @testdox timeInSeconds() は休憩時間を秒数で返す。
     *
     * @group model
     */
    public function testTimeInSeconds()
    {
        $breakTiming = new BreakTiming([
            'begun_at' => '2021-01-01 12:00:00',
            'ended_at' => '2021-01-01 13:00:00',
        ]);

        $this->assertEquals(60 * 60, $breakTiming->timeInSeconds());
    }

    /**
     * @testdox timeInSeconds() は休憩終了せずに日付を跨いだ場合の休憩時間では null を返す。
     *
     * @group model
     */
    public function testTimeInSecondsReturnsNullWhenBreakCrossesDate()
    {
        $breakTiming = new BreakTiming([
            'begun_at' => '2021-01-01 12:00:00',
            'ended_at' => null,
        ]);

        $this->assertNull($breakTiming->timeInSeconds());
    }
}

<?php

declare(strict_types=1);

namespace App;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use DateTimeInterface;

class TimeStamper
{
    public function __construct(private readonly User $user, private readonly DateTimeInterface $now)
    {
        //
    }

    /** 勤務開始処理を行う。 */
    public function beginShift(): void
    {
        $this->handlePreviousShift();

        $now = ShiftTiming::cancelShift($this->user, $this->now) ?? $this->now;
        ShiftBegin::beginShift($this->user, $now);
    }

    /** 勤務終了処理を行う。 */
    public function endShift(): void
    {
        $this->handlePreviousShift();

        $shiftBegin = ShiftBegin::currentShift($this->user, $this->now)->first();
        if ($shiftBegin) {
            ShiftTiming::endShift($shiftBegin, $this->now);
            $shiftBegin->delete();
        }
    }

    /** 休憩開始処理を行う。 */
    public function beginBreak(): void
    {
        $this->handlePreviousShift();
        $this->handlePreviousBreak();

        $shiftBegin = ShiftBegin::currentShift($this->user, $this->now)->first();
        if ($shiftBegin) {
            BreakBegin::beginBreak($this->user, $this->now);
        }
    }

    /** 休憩終了処理を行う。 */
    public function endBreak(): void
    {
        $this->handlePreviousShift();
        $this->handlePreviousBreak();

        $breakBegin = BreakBegin::currentBreak($this->user, $this->now)->first();
        if ($breakBegin) {
            BreakTiming::endBreak($breakBegin, $this->now);
            $breakBegin->delete();
        }
    }

    /** 勤務終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousShift(): void
    {
        $previousBegins = ShiftBegin::previousShift($this->user, $this->now)->get();
        foreach ($previousBegins as $shiftBegin) {
            ShiftTiming::endShift($shiftBegin, null);
            $shiftBegin->delete();
        }
    }

    /** 休憩終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousBreak(): void
    {
        $previousBegins = BreakBegin::previousBreak($this->user, $this->now)->get();
        foreach ($previousBegins as $breakBegin) {
            BreakTiming::create([
                'user_id' => $breakBegin->user_id,
                'begun_at' => $breakBegin->begun_at,
                'ended_at' => null,
            ]);
            $breakBegin->delete();
        }
    }
}

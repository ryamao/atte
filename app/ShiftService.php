<?php

declare(strict_types=1);

namespace App;

use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use DateTimeInterface;

class ShiftService
{
    public function __construct(private readonly User $user, private readonly DateTimeInterface $now)
    {
        //
    }

    /** 勤務開始処理を行う。 */
    public function beginShift(): void
    {
        $this->handlePreviousShift();

        $begunAt = ShiftTiming::cancelShift($this->user, $this->now) ?? $this->now;
        ShiftBegin::beginShift($this->user, $begunAt);
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

    /** 勤務終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousShift(): void
    {
        $shiftBegin = ShiftBegin::previousShift($this->user, $this->now)->first();
        if ($shiftBegin) {
            ShiftTiming::endShift($shiftBegin, null);
            $shiftBegin->delete();
        }
    }
}

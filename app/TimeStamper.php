<?php

declare(strict_types=1);

namespace App;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use DateTimeInterface;

/** 複数のモデルを操作して打刻処理を実行するクラス */
class TimeStamper
{
    /**
     * @param User  $user  打刻を行う会員
     * @param DateTimeInterface  $now  打刻を行う日時
     */
    public function __construct(private readonly User $user, private readonly DateTimeInterface $now)
    {
        //
    }

    /** 勤務開始処理を行う。 */
    public function beginShift(): void
    {
        $this->handlePreviousEvents();

        $now = ShiftTiming::cancelShift($this->user, $this->now) ?? $this->now;
        ShiftBegin::beginPeriod($this->user, $now);
    }

    /** 勤務終了処理を行う。 */
    public function endShift(): void
    {
        $this->handlePreviousEvents();

        $breakBegin = BreakBegin::currentBreak($this->user, $this->now)->first();
        if ($breakBegin) return;

        $shiftBegin = ShiftBegin::currentShift($this->user, $this->now)->first();
        if ($shiftBegin) {
            ShiftTiming::endPeriod($shiftBegin, $this->now);
            $shiftBegin->delete();
        }
    }

    /** 休憩開始処理を行う。 */
    public function beginBreak(): void
    {
        $this->handlePreviousEvents();

        $shiftBegin = ShiftBegin::currentShift($this->user, $this->now)->first();
        if ($shiftBegin) {
            BreakBegin::beginPeriod($this->user, $this->now);
        }
    }

    /** 休憩終了処理を行う。 */
    public function endBreak(): void
    {
        $this->handlePreviousEvents();

        $breakBegin = BreakBegin::currentBreak($this->user, $this->now)->first();
        if ($breakBegin) {
            BreakTiming::endPeriod($breakBegin, $this->now);
            $breakBegin->delete();
        }
    }

    /** 開始イベントを終了せずに日付を跨いだ場合の処理を行う。 */
    public function handlePreviousEvents(): void
    {
        $this->handlePreviousShift();
        $this->handlePreviousBreak();
    }

    /** 勤務終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousShift(): void
    {
        $previousBegins = ShiftBegin::previousShift($this->user, $this->now)->get();
        foreach ($previousBegins as $shiftBegin) {
            ShiftTiming::endPeriod($shiftBegin, null);
            $shiftBegin->delete();
        }
    }

    /** 休憩終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousBreak(): void
    {
        $previousBegins = BreakBegin::previousBreak($this->user, $this->now)->get();
        foreach ($previousBegins as $breakBegin) {
            BreakTiming::endPeriod($breakBegin, null);
            $breakBegin->delete();
        }
    }
}

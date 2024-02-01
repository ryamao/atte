<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Support\Facades\DB;

/**
 * 複数のモデルを操作して打刻処理を実行するサービスクラス
 * 
 * @property-read User  $user  打刻を行う会員
 * @property-read DateTimeInterface  $now  打刻を行う日時
 */
class StampService
{
    public function __construct(private readonly User $user, private readonly DateTimeInterface $now)
    {
        //
    }

    /** 開始イベントを終了せずに日付を跨いだ場合の処理を行う。 */
    public function handlePreviousEvents(): void
    {
        $this->handlePreviousShift();
        $this->handlePreviousBreak();
    }

    /** 勤務開始処理を行う。 */
    public function beginShift(): void
    {
        $this->handlePreviousEvents();

        DB::transaction(function () {
            $shiftTiming = ShiftTiming::begunAtDate($this->user, $this->now)->first();

            $begunAt = $this->now;
            if ($shiftTiming) {
                $begunAt = $shiftTiming->begun_at;
                $shiftTiming->delete();
            }

            ShiftBegin::beginPeriod($this->user, $begunAt);
        });
    }

    /** 勤務終了処理を行う。 */
    public function endShift(): void
    {
        $this->handlePreviousEvents();

        DB::transaction(function () {
            $breakBegin = BreakBegin::currentBreak($this->user, $this->now)->first();
            if ($breakBegin) return;

            $shiftBegin = ShiftBegin
                ::currentShift($this->user, $this->now)
                ->lockForUpdate()
                ->first();
            if ($shiftBegin) {
                ShiftTiming::endPeriod($shiftBegin, $this->now);
                $shiftBegin->delete();
            }
        });
    }

    /** 休憩開始処理を行う。 */
    public function beginBreak(): void
    {
        $this->handlePreviousEvents();

        DB::transaction(function () {
            $shiftBegin = ShiftBegin::currentShift($this->user, $this->now)->first();
            if ($shiftBegin) {
                BreakBegin::beginPeriod($this->user, $this->now);
            }
        });
    }

    /** 休憩終了処理を行う。 */
    public function endBreak(): void
    {
        $this->handlePreviousEvents();

        DB::transaction(function () {
            $breakBegin = BreakBegin
                ::currentBreak($this->user, $this->now)
                ->lockForUpdate()
                ->first();
            if ($breakBegin) {
                BreakTiming::endPeriod($breakBegin, $this->now);
                $breakBegin->delete();
            }
        });
    }

    /** 勤務終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousShift(): void
    {
        DB::transaction(function () {
            $previousBegins = ShiftBegin
                ::previousShift($this->user, $this->now)
                ->lockForUpdate()
                ->get();

            foreach ($previousBegins as $shiftBegin) {
                ShiftTiming::endPeriod($shiftBegin, null);
                $shiftBegin->delete();
            }
        });
    }

    /** 休憩終了せずに日付を跨いだ場合の処理を行う。 */
    private function handlePreviousBreak(): void
    {
        DB::transaction(function () {
            $previousBegins = BreakBegin
                ::previousBreak($this->user, $this->now)
                ->lockForUpdate()
                ->get();

            foreach ($previousBegins as $breakBegin) {
                BreakTiming::endPeriod($breakBegin, null);
                $breakBegin->delete();
            }
        });
    }
}

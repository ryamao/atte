<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** 日付別の勤怠情報を取得するサービス */
class AttendanceService
{
    /**
     * サービスを初期化する。
     * 
     * @param \DateTimeInterface $serviceDate  勤怠情報を取得する日付
     */
    public function __construct(private readonly \DateTimeInterface $serviceDate)
    {
        //
    }

    /** 会員ごとの勤怠情報を取得する。 */
    public function attendances(): Builder
    {
        $breakSeconds = $this->getBreakSeconds();
        $havingBreaks = $this->getShiftTimingsHavingBreaks($breakSeconds);
        $notHavingBreaks = $this->getShiftTimingsNotHavingBreaks($havingBreaks);
        $atWork = $this->getShiftTimingsAtWork();
        return $havingBreaks->union($notHavingBreaks)->union($atWork)->with('user');
    }

    /** 会員ごとの休憩時間の秒数を取得する */
    private function getBreakSeconds(): Builder
    {
        return BreakTiming
            ::selectRaw(<<<SQL
                    user_id,
                    CASE
                        WHEN COUNT(*) = COUNT(ended_at) THEN SUM(TIMESTAMPDIFF(SECOND, begun_at, ended_at))
                        ELSE NULL
                    END AS break_seconds
                SQL)
            ->whereDate('begun_at', $this->serviceDate)
            ->groupBy('user_id');
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     * その日に休憩を取ったことの会員のみを対象とする。
     */
    private function getShiftTimingsHavingBreaks(Builder $breakSeconds): Builder
    {
        return ShiftTiming
            ::select([
                'shift_timings.user_id',
                'shift_timings.begun_at as shift_begun_at',
                'shift_timings.ended_at as shift_ended_at',
                DB::raw('TIMESTAMPDIFF(SECOND, shift_timings.begun_at, shift_timings.ended_at) - break_timings.break_seconds AS work_seconds'),
                'break_timings.break_seconds',
            ])
            ->whereDate('shift_timings.begun_at', $this->serviceDate)
            ->joinSub(
                $breakSeconds,
                'break_timings',
                fn ($join) => $join->on('shift_timings.user_id', '=', 'break_timings.user_id')
            );
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     * その日に休憩を取っていない会員のみを対象とする。
     */
    private function getShiftTimingsNotHavingBreaks(Builder $havingBreaks): Builder
    {
        return ShiftTiming
            ::select([
                'shift_timings.user_id',
                'shift_timings.begun_at as shift_begun_at',
                'shift_timings.ended_at as shift_ended_at',
                DB::raw('TIMESTAMPDIFF(SECOND, shift_timings.begun_at, shift_timings.ended_at) AS work_seconds'),
                DB::raw('0 AS break_seconds'),
            ])
            ->whereNotIn('shift_timings.user_id', $havingBreaks->pluck('user_id'))
            ->whereDate('shift_timings.begun_at', $this->serviceDate);
    }

    /**
     * 勤務中の会員の勤怠情報を取得する。
     * 休憩中の場合は break_seconds に null を設定する。
     * 休憩中でなければ break_seconds に 0 を設定する。
     */
    private function getShiftTimingsAtWork(): Builder
    {
        return ShiftBegin
            ::select([
                'shift_begins.user_id',
                'shift_begins.begun_at as shift_begun_at',
                DB::raw('NULL AS shift_ended_at'),
                DB::raw('NULL AS work_seconds'),
                DB::raw(<<<SQL
                    CASE
                        WHEN break_begins.id IS NOT NULL THEN NULL
                        WHEN break_timings.id IS NOT NULL THEN TIMESTAMPDIFF(SECOND, break_timings.begun_at, break_timings.ended_at)
                        ELSE 0
                    END AS break_seconds
                SQL),
            ])
            ->whereDate('shift_begins.begun_at', $this->serviceDate)
            ->leftJoin('break_begins', 'shift_begins.user_id', '=', 'break_begins.user_id')
            ->leftJoin('break_timings', 'shift_begins.user_id', '=', 'break_timings.user_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BreakTiming;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

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

    /**
     * 会員ごとに指定の日付の勤務時間を秒数で計算する。
     * 休憩終了を打刻していない会員には休憩時間を null で設定する。
     * 休憩中の会員には休憩時間を null で設定する。
     * 休憩を取っていない会員には休憩時間を 0 で設定する。
     */
    public function breakSeconds(): Builder
    {
        $breakTimings = BreakTiming
            ::selectRaw(<<<SQL
                    user_id,
                    CASE
                        WHEN COUNT(*) = COUNT(ended_at) THEN
                            SUM(TIMESTAMPDIFF(SECOND, begun_at, ended_at))
                        ELSE
                            NULL
                    END AS break_seconds
                SQL)
            ->whereDate('begun_at', $this->serviceDate)
            ->groupBy('user_id');

        return User
            ::selectRaw(<<<SQL
                    users.id as user_id,
                    CASE
                        WHEN break_begins.user_id IS NOT NULL THEN
                            NULL
                        WHEN break_timings.user_id IS NULL THEN
                            0
                        ELSE
                            break_timings.break_seconds
                    END AS break_seconds
                SQL)
            ->leftJoin('break_begins', 'users.id', '=', 'break_begins.user_id')
            ->leftJoinSub(
                $breakTimings,
                'break_timings',
                fn ($join) => $join->on('users.id', '=', 'break_timings.user_id')
            );
    }

    /**
     * 会員ごとの勤務時間の秒数を取得する。
     * 勤務中の会員には休憩時間を null で設定する。
     * 指定の日付に勤務開始していない会員は結果に含めない。
     */
    public function shiftSeconds(): Builder
    {
        return User
            ::selectRaw(<<<SQL
                    users.id as user_id,
                    IFNULL(shift_begins.begun_at, shift_timings.begun_at) as begun_at,
                    shift_timings.ended_at as ended_at,
                    CASE
                        WHEN shift_begins.id IS NOT NULL THEN
                            NULL
                        ELSE
                            TIMESTAMPDIFF(SECOND, shift_timings.begun_at, shift_timings.ended_at)
                    END AS shift_seconds
                SQL)
            ->leftJoin('shift_begins', 'users.id', '=', 'shift_begins.user_id')
            ->leftJoin('shift_timings', 'users.id', '=', 'shift_timings.user_id')
            ->whereDate('shift_timings.begun_at', $this->serviceDate)
            ->orWhereDate('shift_begins.begun_at', $this->serviceDate);
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     * 指定の日付に勤務開始していない会員は結果に含めない。
     */
    public function attendances(): Builder
    {
        return User
            ::selectRaw(<<<SQL
                    users.name as user_name,
                    IFNULL(shift_begins.begun_at, shift_timings.begun_at) as shift_begun_at,
                    shift_timings.ended_at as shift_ended_at,
                    CASE
                        WHEN shift_begins.id IS NOT NULL THEN
                            NULL
                        ELSE
                            TIMESTAMPDIFF(SECOND, shift_timings.begun_at, shift_timings.ended_at) - break_seconds.break_seconds
                    END AS work_seconds,
                    break_seconds.break_seconds
                SQL)
            ->leftJoin('shift_begins', 'users.id', '=', 'shift_begins.user_id')
            ->leftJoin('shift_timings', 'users.id', '=', 'shift_timings.user_id')
            ->joinSub(
                $this->breakSeconds(),
                'break_seconds',
                fn ($join) => $join->on('users.id', '=', 'break_seconds.user_id')
            )
            ->whereDate('shift_timings.begun_at', $this->serviceDate)
            ->orWhereDate('shift_begins.begun_at', $this->serviceDate)
            ->orderBy('users.name');
    }
}

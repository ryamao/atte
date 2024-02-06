<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/** 日付別の勤怠情報を取得するサービスクラス */
class DailyAttendanceService
{
    /**
     * サービスを初期化する。
     *
     * @param  \DateTimeInterface  $serviceDate  勤怠情報を取得する日付
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
        // 指定の日付における休憩時間を計算する
        $breakTimings = BreakTiming::selectRaw(<<<'SQL'
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

        // 指定の日付における休憩中の会員を取得する
        $breakBegins = BreakBegin::selectRaw('user_id')
            ->whereDate('begun_at', $this->serviceDate);

        return User::selectRaw(<<<'SQL'
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
            ->leftJoinSub(
                $breakTimings,
                'break_timings',
                fn ($join) => $join->on('users.id', '=', 'break_timings.user_id')
            )->leftJoinSub(
                $breakBegins,
                'break_begins',
                fn ($join) => $join->on('users.id', '=', 'break_begins.user_id')
            )->withCasts([
                'break_seconds' => 'integer',
            ]);
    }

    /**
     * 会員ごとの勤務時間の秒数を取得する。
     * 勤務中の会員には休憩時間を null で設定する。
     * 指定の日付に勤務開始していない会員は結果に含めない。
     */
    public function shiftSeconds(): Builder
    {
        $shiftTimings = ShiftTiming::whereDate('begun_at', $this->serviceDate);

        $shiftBegins = ShiftBegin::selectRaw('*, NULL AS ended_at')
            ->whereDate('begun_at', $this->serviceDate);

        return User::selectRaw(<<<'SQL'
                    users.id AS user_id,
                    shift_timings.begun_at AS shift_begun_at,
                    shift_timings.ended_at AS shift_ended_at,
                    CASE
                        WHEN shift_timings.id IS NULL THEN
                            0
                        WHEN shift_timings.ended_at IS NULL THEN
                            NULL
                        ELSE
                            TIMESTAMPDIFF(SECOND, shift_timings.begun_at, shift_timings.ended_at)
                    END AS shift_seconds
                SQL)
            ->leftJoinSub(
                $shiftTimings->union($shiftBegins),
                'shift_timings',
                fn ($join) => $join->on('users.id', '=', 'shift_timings.user_id')
            )->withCasts([
                'shift_begun_at' => 'immutable_datetime:Y-m-d H:i:s',
                'shift_ended_at' => 'immutable_datetime:Y-m-d H:i:s',
                'shift_seconds' => 'integer',
            ]);
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     */
    public function attendances(): Builder
    {
        return DB::transaction(function () {
            return User::selectRaw(<<<'SQL'
                        users.id AS user_id,
                        users.name AS user_name,
                        shift_seconds.shift_begun_at,
                        shift_seconds.shift_ended_at,
                        break_seconds.break_seconds,
                        CASE
                            WHEN shift_seconds.shift_seconds IS NULL THEN
                                NULL
                            WHEN break_seconds.break_seconds IS NULL THEN
                                NULL
                            ELSE
                                shift_seconds.shift_seconds - break_seconds.break_seconds
                        END AS work_seconds
                    SQL)
                ->leftJoinSub(
                    $this->breakSeconds(),
                    'break_seconds',
                    fn ($join) => $join->on('users.id', '=', 'break_seconds.user_id')
                )->leftJoinSub(
                    $this->shiftSeconds(),
                    'shift_seconds',
                    fn ($join) => $join->on('users.id', '=', 'shift_seconds.user_id')
                )->withCasts([
                    'shift_begun_at' => 'immutable_date:Y-m-d H:i:s',
                    'shift_ended_at' => 'immutable_date:Y-m-d H:i:s',
                    'break_seconds' => 'integer',
                    'work_seconds' => 'integer',
                ])->sharedLock();
        });
    }
}

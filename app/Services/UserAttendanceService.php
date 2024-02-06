<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\CalendarDate;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/** 会員別の勤怠情報を取得するサービス */
class UserAttendanceService
{
    private readonly CarbonImmutable $date;

    /**
     * サービスを初期化する。
     *
     * @param  \App\Models\User  $user  勤怠情報を取得する会員
     * @param  \Carbon\CarbonImmutable  $date  勤怠情報を取得する年月
     */
    public function __construct(private readonly User $user, \DateTimeInterface $date)
    {
        $this->date = CarbonImmutable::instance($date);
    }

    /**
     * 指定年月内の日付を取得する。
     */
    public function calendarDates(): Builder
    {
        $firstOfMonth = $this->date->firstOfMonth();
        $firstOfCurrentMonth = CarbonImmutable::today()->firstOfMonth();
        $lastOfMonth = $firstOfMonth->greaterThanOrEqualTo($firstOfCurrentMonth) ? $this->date->today() : $this->date->lastOfMonth();

        $needsToCreate = CalendarDate::whereDate('date', $lastOfMonth)->doesntExist();
        if ($needsToCreate) {
            $this->createCalendarDates($firstOfMonth);
        }

        return CalendarDate::select('date')
            ->whereYear('date', $this->date->year)
            ->whereMonth('date', $this->date->month);
    }

    /**
     * 指定年月内の日付ごとに、個別会員の勤務時間を秒数で取得する。
     * 会員が勤務していない日付の勤務時間は 0 秒で設定する。
     * 会員が勤務中の日付の勤務時間は出力しない。
     */
    public function shiftTimesInSeconds(): Builder
    {
        return $this->calendarDates()
            ->leftJoinSub(
                $this->shiftTimeInSecondsInMonth($this->date),
                'st',
                fn ($join) => $join->on('calendar_dates.date', 'shift_date')
            )
            ->whereNotIn(
                'calendar_dates.date',
                $this->user->shiftBegin()->selectRaw('CAST(begun_at AS DATE) AS shift_date')
            )
            ->selectRaw(<<<'SQL'
                shift_date,
                shift_begun_at,
                shift_ended_at,
                COALESCE(shift_seconds, 0) AS shift_seconds
            SQL);
    }

    /**
     * 指定年月内の日付ごとに、個別会員の休憩時間を秒数で取得する。
     * 会員が休憩していない日付の休憩時間は 0 秒で設定する。
     * 会員が休憩中の日付の休憩時間は出力しない。
     */
    public function breakTimesInSeconds(): Builder
    {
        return $this->calendarDates()
            ->leftJoinSub(
                $this->breakTimeInSecondsInMonth($this->date),
                'bt',
                fn ($join) => $join->on('calendar_dates.date', '.break_date')
            )
            ->whereNotIn(
                'calendar_dates.date',
                $this->user->breakBegin()->selectRaw('CAST(begun_at AS DATE) AS break_date')
            )
            ->selectRaw(<<<'SQL'
                break_date,
                COALESCE(break_seconds, 0) AS break_seconds
            SQL);
    }

    /**
     * 指定年月内の日付ごとに、個別会員の勤怠情報を取得する。
     * 会員が勤務していない日付の労働時間は 0 秒で設定する。
     * 会員が勤務中の日付の労働時間は出力しない。
     */
    public function attendances(): Builder
    {
        return $this->calendarDates()
            ->leftJoinSub(
                $this->shiftTimeInSecondsInMonth($this->date),
                'st',
                fn ($join) => $join->on('calendar_dates.date', 'shift_date')
            )
            ->leftJoinSub(
                $this->breakTimeInSecondsInMonth($this->date),
                'bt',
                fn ($join) => $join->on('calendar_dates.date', 'break_date')
            )
            ->whereNotIn(
                'calendar_dates.date',
                $this->user->shiftBegin()->selectRaw('CAST(begun_at AS DATE) AS shift_date')
            )
            ->whereNotIn(
                'calendar_dates.date',
                $this->user->breakBegin()->selectRaw('CAST(begun_at AS DATE) AS break_date')
            )
            ->selectRaw(<<<'SQL'
                calendar_dates.date,
                shift_begun_at,
                shift_ended_at,
                shift_seconds,
                break_seconds,
                shift_seconds - break_seconds AS work_seconds
            SQL)
            ->withCasts([
                'shift_begun_at' => 'immutable_datetime:Y-m-d H:i:s',
                'shift_ended_at' => 'immutable_datetime:Y-m-d H:i:s',
                'break_seconds' => 'integer',
                'work_seconds' => 'integer',
            ]);
    }

    /**
     * 指定年月内の日付をカレンダーテーブルに保存する。
     */
    private function createCalendarDates(CarbonImmutable $firstOfMonth): void
    {
        $firstOfCurrentMonth = CarbonImmutable::today()->firstOfMonth();
        $lastOfMonth = $firstOfMonth->greaterThanOrEqualTo($firstOfCurrentMonth) ? $this->date->today() : $this->date->lastOfMonth();

        $dates = $firstOfMonth->daysUntil($lastOfMonth);
        foreach ($dates as $date) {
            CalendarDate::firstOrCreate(['date' => $date]);
        }
    }

    /**
     * 会員と年月を指定して勤務時間を取得する。
     * 指定の日付に勤務開始していない会員は結果に含めない。
     */
    private function shiftTimeInSecondsInMonth(CarbonImmutable $date): Relation
    {
        return $shiftTimings = $this->user
            ->shiftTimings()
            ->whereYear('begun_at', $date->year)
            ->whereMonth('begun_at', $date->month)
            ->selectRaw(<<<'SQL'
                CAST(begun_at AS DATE) AS shift_date,
                begun_at AS shift_begun_at,
                ended_at AS shift_ended_at,
                TIMESTAMPDIFF(SECOND, begun_at, ended_at) AS shift_seconds
            SQL);
    }

    /**
     * 会員と年月を指定して休憩時間を取得する。
     * 指定の日付に休憩開始していない会員は結果に含めない。
     */
    private function breakTimeInSecondsInMonth(CarbonImmutable $date): Relation
    {
        return $breakTimings = $this->user
            ->breakTimings()
            ->whereYear('begun_at', $date->year)
            ->whereMonth('begun_at', $date->month)
            ->selectRaw(<<<'SQL'
                CAST(begun_at AS DATE) AS break_date,
                SUM(TIMESTAMPDIFF(SECOND, begun_at, ended_at)) AS break_seconds
            SQL)
            ->groupBy('break_date');
    }
}

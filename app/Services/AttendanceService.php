<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/** 日付別の勤怠情報を取得するサービス */
class AttendanceService
{
    public function __construct(private readonly \DateTimeInterface $today)
    {
        //
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     *
     * @return Collection<array{
     *      userName: string,
     *      shiftBegunAt: CarbonImmutable,
     *      shiftEndedAt: CarbonImmutable|null,
     *      breakingSeconds: int|null,
     *      workingSeconds: int|null
     *  }>
     */
    public function getAttendances(): Collection
    {
        return $this->getBuilder()->get()->map(fn (ShiftTiming $record) => $this->mapAttendance($record));
    }

    /** 会員ごとの勤怠情報を取得する。 */
    public function getBuilder(): Builder
    {
        $breakingSeconds = $this->getBreakingSeconds();
        $havingBreaks = $this->getShiftTimingsHavingBreaks($breakingSeconds);
        $notHavingBreaks = $this->getShiftTimingsNotHavingBreaks($havingBreaks);
        $atWork = $this->getShiftTimingsAtWork();
        return $havingBreaks->union($notHavingBreaks)->union($atWork)->with('user');
    }

    /**
     * データベースから取得した勤怠情報を表示しやすい処理に変換する
     *
     * @return array{
     *      userName: string,
     *      shiftBegunAt: CarbonImmutable,
     *      shiftEndedAt: CarbonImmutable|null,
     *      breakingSeconds: int|null,
     *      workingSeconds: int|null
     *  }
     */
    public function mapAttendance(ShiftTiming $record): array
    {
        $shiftBegunAt = CarbonImmutable::parse($record['begun_at'], $this->today->getTimezone());

        $shiftEndedAt = null;
        if ($record['ended_at'] !== null) {
            $shiftEndedAt = CarbonImmutable::parse($record['ended_at'], $this->today->getTimezone());
        }

        return [
            'userName' => $record->user->name,
            'shiftBegunAt' => $shiftBegunAt,
            'shiftEndedAt' => $shiftEndedAt,
            'breakingSeconds' => $record['breaking_seconds'],
            'workingSeconds' => $record['working_seconds'],
        ];
    }

    /** 会員ごとの休憩時間の秒数を取得する */
    private function getBreakingSeconds(): Builder
    {
        return BreakTiming
            ::selectRaw('user_id, SUM(TIME_TO_SEC(ended_at) - TIME_TO_SEC(begun_at)) AS breaking_seconds')
            ->whereDate('begun_at', $this->today)
            ->groupBy('user_id');
    }

    /**
     * 会員ごとの勤怠情報を取得する。
     * その日に休憩を取ったことの会員のみを対象とする。
     */
    private function getShiftTimingsHavingBreaks(Builder $breakingSeconds): Builder
    {
        return ShiftTiming
            ::select([
                'shift_timings.user_id',
                'shift_timings.begun_at',
                'shift_timings.ended_at',
                DB::raw('TIME_TO_SEC(ended_at) - TIME_TO_SEC(begun_at) - break_timings.breaking_seconds AS working_seconds'),
                'break_timings.breaking_seconds',
            ])
            ->whereDate('begun_at', $this->today)
            ->joinSub(
                $breakingSeconds,
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
                'shift_timings.begun_at',
                'shift_timings.ended_at',
                DB::raw('TIME_TO_SEC(ended_at) - TIME_TO_SEC(begun_at) AS working_seconds'),
                DB::raw('0 AS breaking_seconds'),
            ])
            ->whereNotIn('shift_timings.user_id', $havingBreaks->pluck('user_id'))
            ->whereDate('begun_at', $this->today);
    }

    /**
     * 勤務中の会員の勤怠情報を取得する。
     * 休憩中の場合は breaking_seconds に null を設定する。
     * 休憩中でなければ breaking_seconds に 0 を設定する。
     */
    private function getShiftTimingsAtWork(): Builder
    {
        return ShiftBegin
            ::select([
                'shift_begins.user_id',
                'shift_begins.begun_at',
                DB::raw('NULL AS ended_at'),
                DB::raw('NULL AS working_seconds'),
                DB::raw('IF(break_begins.id IS NOT NULL, NULL, 0) AS breaking_seconds'),
            ])
            ->whereDate('shift_begins.begun_at', $this->today)
            ->leftJoin('break_begins', 'shift_begins.user_id', '=', 'break_begins.user_id');
    }
}

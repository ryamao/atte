<?php

namespace App\View\Components;

use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/** 勤怠情報テーブルの行を表すコンポーネント */
class AttendanceRow extends Component
{
    /**
     * Create a new component instance.
     */
    public function __construct(private Model $attendance)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $userName = $this->attendance->user_name;
        $shiftBegunAt = $this->formatTime($this->attendance->shift_begun_at);
        $shiftEndedAt = $this->formatTime($this->attendance->shift_ended_at);
        $breakSeconds = $this->formatSeconds($this->attendance->break_seconds);
        $workSeconds = $this->formatSeconds($this->attendance->work_seconds);
        return view('components.attendance-row', compact('userName', 'shiftBegunAt', 'shiftEndedAt', 'breakSeconds', 'workSeconds'));
    }

    /** 開始日時や終了日時を表示形式に変換する。日時が null の場合は '--:--:--' を返す。 */
    private function formatTime(?string $datetime): string
    {
        return $datetime ? CarbonImmutable::parse($datetime)->format('H:i:s') : '--:--:--';
    }

    /** 休憩時間や勤務時間を表示形式に変換する。 */
    private function formatSeconds(?int $seconds): string
    {
        return $seconds ? today()->addSeconds($seconds)->format('H:i:s') : '--:--:--';
    }
}

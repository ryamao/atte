<?php

declare(strict_types=1);

namespace App\View\Components;

use App\Traits\FormatTimeAndSeconds;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

/** 勤怠情報テーブルの行を表すコンポーネント */
class AttendanceRow extends Component
{
    use FormatTimeAndSeconds;

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
}

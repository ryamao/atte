<?php

namespace App\View\Components;

use App\Traits\FormatTimeAndSeconds;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\View\Component;

class UserAttendanceRow extends Component
{
    use FormatTimeAndSeconds;

    /**
     * Create a new component instance.
     */
    public function __construct(private readonly Model $attendance)
    {
        //
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        $date = $this->attendance->date->format('Y-m-d');
        $shiftBegunAt = $this->formatTime($this->attendance->shift_begun_at);
        $shiftEndedAt = $this->formatTime($this->attendance->shift_ended_at);
        $breakSeconds = $this->formatSeconds($this->attendance->break_seconds);
        $workSeconds = $this->formatSeconds($this->attendance->work_seconds);

        return view('components.user-attendance-row', compact('date', 'shiftBegunAt', 'shiftEndedAt', 'breakSeconds', 'workSeconds'));
    }
}

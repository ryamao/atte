<x-app-layout>
    <x-slot name="css">attendance.css</x-slot>

    <div class="attendance">
        <div class="attendance__date">
            <a href="{{ route('attendance', ['date' => $currentDate->subDay()->format('Y-m-d')]) }}" dusk="previous-date">&lt;</a>
            <span class="attendance__current-date" dusk="current-date">{{ $currentDate->format('Y-m-d') }}</span>
            <a href="{{ route('attendance', ['date' => $currentDate->addDay()->format('Y-m-d')]) }}" dusk="next-date">&gt;</a>
        </div>

        <table class="attendance__table">
            <thead>
                <tr>
                    <th>名前</th>
                    <th>勤務開始</th>
                    <th>勤務終了</th>
                    <th>休憩時間</th>
                    <th>勤務時間</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                <x-attendance-row :$attendance />
                @endforeach
            </tbody>
        </table>

        <div class="attendance__pagination">{{ $attendances->links() }}</div>
    </div>
</x-app-layout>
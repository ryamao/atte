<x-app-layout>
    <a href="{{ route('attendance', ['date' => $current_date->subDay()->format('Y-m-d')]) }}" dusk="previous-date">&lt;</a>
    <div dusk="current-date">{{ $current_date->format('Y-m-d') }}</div>
    <a href="{{ route('attendance', ['date' => $current_date->addDay()->format('Y-m-d')]) }}" dusk="next-date">&gt;</a>
    <table>
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
            <tr>
                <td>{{ $attendance->user_name }}</td>
                <td>{{ $attendance->shift_begun_at }}</td>
                <td>{{ $attendance->shift_ended_at }}</td>
                <td>{{ today()->addSeconds($attendance->break_seconds)->format('H:i:s') }}</td>
                <td>{{ today()->addSeconds($attendance->work_seconds)->format('H:i:s') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    {{ $attendances->links() }}
</x-app-layout>
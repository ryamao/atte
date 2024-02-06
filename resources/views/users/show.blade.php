<x-app-layout :css="['components/pagination.css', 'users/show.css']">
    <div class="user">
        <h2 class="user__name">{{ $userName }}</h2>

        <div class="user__month-selector">
            <a href="{{ route('users.show', ['user' => $user, 'ym' => $currentMonth->subMonth()->format('Y-m')]) }}" dusk="previous-month">&lt;</a>
            <span class="user__current-month" dusk="current-month">{{ $currentMonth->format('Y-m') }}</span>
            <a href="{{ route('users.show', ['user' => $user, 'ym' => $currentMonth->addMonth()->format('Y-m')]) }}" dusk="next-month">&gt;</a>
        </div>

        <table class="user__attendance-table">
            <thead>
                <tr>
                    <th>日付</th>
                    <th>勤務開始</th>
                    <th>勤務終了</th>
                    <th>休憩時間</th>
                    <th>勤務時間</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($attendances as $attendance)
                    <x-user-attendance-row :$attendance />
                @endforeach
            </tbody>
        </table>

        <div class="user__pagination">
            {{ $attendances->links() }}
        </div>
    </div>
</x-app-layout>

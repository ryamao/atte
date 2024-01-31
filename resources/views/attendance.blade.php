@php
use Carbon\CarbonImmutable;
@endphp

<x-app-layout>
    <a href="{{ route('attendance', ['date' => $currentDate->subDay()->format('Y-m-d')]) }}" dusk="previous-date">&lt;</a>
    <div dusk="current-date">{{ $currentDate->format('Y-m-d') }}</div>
    <a href="{{ route('attendance', ['date' => $currentDate->addDay()->format('Y-m-d')]) }}" dusk="next-date">&gt;</a>
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
            <x-attendance-row :$attendance />
            @endforeach
        </tbody>
    </table>
    {{ $attendances->links() }}
</x-app-layout>
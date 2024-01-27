@php
use App\WorkStatus;
@endphp

<x-app-layout>
    <h2 dusk="gratitude">{{ $userName }}さんお疲れ様です！</h2>
    <button dusk="shift-begin" @disabled($workStatus!=WorkStatus::Before)>勤務開始</button>
    <button dusk="shift-end" @disabled($workStatus!=WorkStatus::During)>勤務終了</button>
    <button dusk="break-begin" @disabled($workStatus!=WorkStatus::During)>休憩開始</button>
    <button dusk="break-end" @disabled($workStatus!=WorkStatus::Break)>休憩終了</button>
</x-app-layout>
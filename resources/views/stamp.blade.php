@php
use App\WorkStatus;
@endphp

<x-app-layout>
    <x-slot name="css">stamp.css</x-slot>

    <div class="stamp">
        <h2 class="stamp__title" dusk="gratitude">{{ $userName }}さんお疲れ様です！</h2>

        <div class="stamp__layout">
            <form action="{{ route('shift-begin') }}" method="post">
                @csrf
                <button dusk="shift-begin" @disabled($workStatus!=WorkStatus::Before)>勤務開始</button>
            </form>
            <form action="{{ route('shift-end') }}" method="post">
                @csrf
                <button dusk="shift-end" @disabled($workStatus!=WorkStatus::During)>勤務終了</button>
            </form>
            <form action="{{ route('break-begin') }}" method="post">
                @csrf
                <button dusk="break-begin" @disabled($workStatus!=WorkStatus::During)>休憩開始</button>
            </form>
            <form action="{{ route('break-end') }}" method="post">
                @csrf
                <button dusk="break-end" @disabled($workStatus!=WorkStatus::Break)>休憩終了</button>
            </form>
        </div>
    </div>
</x-app-layout>
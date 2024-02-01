<x-app-layout>
    <x-slot name="css">stamp.css</x-slot>

    <div class="stamp">
        <h2 class="stamp__title" dusk="gratitude">{{ $userName }}さんお疲れ様です！</h2>

        <div class="stamp__layout">
            <form action="{{ route('shift-begin') }}" method="post">
                @csrf
                <button dusk="shift-begin" @disabled($workStatus->isDuring() || $workStatus->isBreak())>勤務開始</button>
            </form>
            <form action="{{ route('shift-end') }}" method="post">
                @csrf
                <button dusk="shift-end" @disabled($workStatus->isBefore() || $workStatus->isBreak())>勤務終了</button>
            </form>
            <form action="{{ route('break-begin') }}" method="post">
                @csrf
                <button dusk="break-begin" @disabled($workStatus->isBefore() || $workStatus->isBreak())>休憩開始</button>
            </form>
            <form action="{{ route('break-end') }}" method="post">
                @csrf
                <button dusk="break-end" @disabled($workStatus->isBefore() || $workStatus->isDuring())>休憩終了</button>
            </form>
        </div>
    </div>
</x-app-layout>
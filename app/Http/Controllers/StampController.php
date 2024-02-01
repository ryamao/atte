<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\StampService;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;

/** 打刻ページと打刻機能のコントローラ */
class StampController extends Controller
{
    /** 打刻ページを表示する。 */
    public function index(): View
    {
        $now = CarbonImmutable::now(app()->make(DateTimeZone::class));
        $service = new StampService(Auth::user(), $now);
        $service->handlePreviousEvents();

        /** @var \App\Models\User */
        $user = Auth::user();

        return view('stamp', [
            'userName' => $user->name,
            'workStatus' => $user->workStatus($now),
        ]);
    }

    /** 勤務開始処理を行う。 */
    public function storeShiftBegin(): RedirectResponse
    {
        return $this->storeStamp(fn (StampService $service) => $service->beginShift());
    }

    /** 勤務終了処理を行う。 */
    public function storeShiftTiming(): RedirectResponse
    {
        return $this->storeStamp(fn (StampService $service) => $service->endShift());
    }

    /** 休憩開始処理を行う。 */
    public function storeBreakBegin(): RedirectResponse
    {
        return $this->storeStamp(fn (StampService $service) => $service->beginBreak());
    }

    /** 休憩終了処理を行う。 */
    public function storeBreakTiming(): RedirectResponse
    {
        return $this->storeStamp(fn (StampService $service) => $service->endBreak());
    }

    /**
     * 打刻処理を行う。
     * 
     * @param callable(StampService):void $callback
     * @return RedirectResponse
     */
    private function storeStamp(callable $callback): RedirectResponse
    {
        $now = CarbonImmutable::now(app()->make(DateTimeZone::class));
        $service = new StampService(Auth::user(), $now);
        $callback($service);
        return redirect()->route('stamp');
    }
}

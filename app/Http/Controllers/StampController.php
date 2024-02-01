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
use App\WorkStatus;

/** 打刻ページと打刻機能のコントローラ */
class StampController extends Controller
{
    /** 打刻ページを表示する。 */
    public function index(DateTimeZone $timezone): View
    {
        $this->stamper()->handlePreviousEvents();

        $user = Auth::user();
        $now = CarbonImmutable::now($timezone);
        return view('stamp', [
            'userName' => $user->name,
            'workStatus' => $user->workStatus($now),
        ]);
    }

    /** 勤務開始処理を行う。 */
    public function storeShiftBegin(): RedirectResponse
    {
        $this->stamper()->beginShift();
        return redirect()->route('stamp');
    }

    /** 勤務終了処理を行う。 */
    public function storeShiftTiming(): RedirectResponse
    {
        $this->stamper()->endShift();
        return redirect()->route('stamp');
    }

    /** 休憩開始処理を行う。 */
    public function storeBreakBegin(): RedirectResponse
    {
        $this->stamper()->beginBreak();
        return redirect()->route('stamp');
    }

    /** 休憩終了処理を行う。 */
    public function storeBreakTiming(): RedirectResponse
    {
        $this->stamper()->endBreak();
        return redirect()->route('stamp');
    }

    /** 認証ユーザと現在日時で StampService を作成する。 */
    private function stamper(): StampService
    {
        return App::call(function (DateTimeZone $timezone) {
            return new StampService(Auth::user(), CarbonImmutable::now($timezone));
        });
    }
}

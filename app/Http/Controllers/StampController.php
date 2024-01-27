<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\TimeStamper;
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
        $this->stamper()->handlePreviousEvents();
        return view('stamp', ['userName' => Auth::user()->name]);
    }

    /** 勤務開始処理を行う。 */
    public function storeShiftBegin(): RedirectResponse
    {
        $this->stamper()->beginShift();
        return redirect(route('stamp'));
    }

    /** 勤務終了処理を行う。 */
    public function storeShiftTiming(): RedirectResponse
    {
        $this->stamper()->endShift();
        return redirect(route('stamp'));
    }

    /** 休憩開始処理を行う。 */
    public function storeBreakBegin(): RedirectResponse
    {
        $this->stamper()->beginBreak();
        return redirect(route('stamp'));
    }

    /** 休憩終了処理を行う。 */
    public function storeBreakTiming(): RedirectResponse
    {
        $this->stamper()->endBreak();
        return redirect(route('stamp'));
    }

    /** 認証ユーザと現在日時で TimeStamper を作成する。 */
    private function stamper(): TimeStamper
    {
        return App::call(function (DateTimeZone $timezone) {
            return new TimeStamper(Auth::user(), CarbonImmutable::now($timezone));
        });
    }
}

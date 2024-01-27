<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\TimeStamper;
use Carbon\CarbonImmutable;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    /** 打刻ページを表示する。 */
    public function index(): View
    {
        return view('stamp', ['userName' => Auth::user()->name]);
    }

    /** 勤務開始処理を行う。 */
    public function storeShiftBegin(DateTimeZone $timezone): RedirectResponse
    {
        $stamper = new TimeStamper(Auth::user(), CarbonImmutable::now($timezone));
        $stamper->beginShift();
        return redirect(route('stamp'));
    }

    /** 勤務終了処理を行う。 */
    public function storeShiftEnd(DateTimeZone $timezone): RedirectResponse
    {
        $stamper = new TimeStamper(Auth::user(), CarbonImmutable::now($timezone));
        $stamper->endShift();
        return redirect(route('stamp'));
    }

    /** 休憩開始処理を行う。 */
    public function storeBreakBegin(DateTimeZone $timezone): RedirectResponse
    {
        $stamper = new TimeStamper(Auth::user(), CarbonImmutable::now($timezone));
        $stamper->beginBreak();
        return redirect(route('stamp'));
    }

    /** 休憩終了処理を行う。 */
    public function storeBreakTiming(DateTimeZone $timezone): RedirectResponse
    {
        $stamper = new TimeStamper(Auth::user(), CarbonImmutable::now($timezone));
        $stamper->endBreak();
        return redirect(route('stamp'));
    }
}

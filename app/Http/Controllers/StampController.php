<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\BreakBegin;
use App\Models\BreakTiming;
use App\Models\ShiftBegin;
use App\Models\ShiftTiming;
use App\ShiftService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StampController extends Controller
{
    /** 打刻ページを表示する。 */
    public function index(Request $request): View
    {
        return view('stamp', ['userName' => Auth::user()->name]);
    }

    /** 勤務開始処理を行う。 */
    public function storeShiftBegin(Request $request): RedirectResponse
    {
        $shiftService = new ShiftService(Auth::user(), CarbonImmutable::now());
        $shiftService->beginShift();
        return redirect(route('stamp'));
    }

    /** 勤務終了処理を行う。 */
    public function storeShiftEnd(Request $request): RedirectResponse
    {
        $shiftService = new ShiftService(Auth::user(), CarbonImmutable::now());
        $shiftService->endShift();
        return redirect(route('stamp'));
    }

    /** 休憩開始処理を行う。 */
    public function storeBreakBegin(Request $request): RedirectResponse
    {
        $now = CarbonImmutable::now();

        $shiftBegin = ShiftBegin::previousShift(Auth::user(), $now)->first();
        if ($shiftBegin) {
            ShiftTiming::endShift($shiftBegin, null);
            $shiftBegin->delete();
        }

        $breakBegin = BreakBegin
            ::where('user_id', Auth::user()->id)
            ->whereDate('begun_at', '<', $now)
            ->first();
        if ($breakBegin) {
            BreakTiming::create([
                'user_id' => $breakBegin->user_id,
                'begun_at' => $breakBegin->begun_at,
                'ended_at' => null,
            ]);
            $breakBegin->delete();
        }

        $shiftBegin = ShiftBegin::currentShift(Auth::user(), $now)->first();
        if ($shiftBegin) {
            BreakBegin::firstOrCreate(
                ['user_id' => Auth::user()->id],
                ['begun_at' => $now],
            );
        }

        return redirect(route('stamp'));
    }

    /** 休憩終了処理を行う。 */
    public function storeBreakTiming(Request $request): RedirectResponse
    {
        $now = CarbonImmutable::now();

        $shiftBegin = ShiftBegin::previousShift(Auth::user(), $now)->first();
        if ($shiftBegin) {
            ShiftTiming::endShift($shiftBegin, null);
            $shiftBegin->delete();
        }

        $breakBegin = BreakBegin
            ::where('user_id', Auth::user()->id)
            ->whereDate('begun_at', '<', $now)
            ->first();
        if ($breakBegin) {
            BreakTiming::create([
                'user_id' => $breakBegin->user_id,
                'begun_at' => $breakBegin->begun_at,
                'ended_at' => null,
            ]);
            $breakBegin->delete();
        }

        $breakBegin = BreakBegin
            ::where('user_id', Auth::user()->id)
            ->whereDate('begun_at', $now)
            ->first();
        if ($breakBegin) {
            BreakTiming::create([
                'user_id' => $breakBegin->user_id,
                'begun_at' => $breakBegin->begun_at,
                'ended_at' => $now,
            ]);
            $breakBegin->delete();
        }

        return redirect(route('stamp'));
    }
}

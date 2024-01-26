<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeZone;
use Illuminate\Contracts\View\View;
use Throwable;

/** 日付別勤怠ページを表示するコントローラ */
class AttendanceController extends Controller
{
    /** 日付別勤怠ページを表示する */
    public function index(DateTimeZone $timezone): View
    {
        $currentDate = $this->getDateFromQueryString($timezone);

        try {
            $service = new AttendanceService($currentDate);
            $attendances = $service
                ->attendances()
                ->whereNotNull('shift_begun_at')
                ->orderBy('user_name')
                ->orderBy('user_id')
                ->paginate(5)
                ->withQueryString();

            return view('attendance', compact('attendances', 'currentDate'));
        } catch (Throwable $e) {
            // TODO 共有ロックに失敗した場合の処理を追加する
            abort(500, $e->getMessage());
        }
    }

    /** クエリストリングから日付を取得する */
    private function getDateFromQueryString(DateTimeZone $timezone): CarbonImmutable
    {
        $date = request()->query('date');
        if (is_null($date)) {
            return CarbonImmutable::today($timezone);
        }

        try {
            return CarbonImmutable::parse($date, $timezone);
        } catch (InvalidFormatException $e) {
            return CarbonImmutable::today($timezone);
        }
    }
}

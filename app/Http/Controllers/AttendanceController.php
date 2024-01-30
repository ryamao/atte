<?php

namespace App\Http\Controllers;

use App\Services\AttendanceService;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DateTimeZone;
use Illuminate\Contracts\View\View;

/** 日付別勤怠ページを表示するコントローラ */
class AttendanceController extends Controller
{
    /** 日付別勤怠ページを表示する */
    public function index(DateTimeZone $timezone): View
    {
        $date = $this->getDateFromQueryString($timezone);
        $service = new AttendanceService($date);
        $query = $service->attendances()->whereNotNull('shift_begun_at')->orderBy('user_name');

        return view('attendance', [
            'current_date' => $date,
            'attendances' => $query->paginate(5),
        ]);
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

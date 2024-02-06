<?php

declare(strict_types=1);

namespace App\Traits;

use Carbon\CarbonImmutable;

trait FormatTimeAndSeconds
{
    /** 開始日時や終了日時を表示形式に変換する。日時が null の場合は '--:--:--' を返す。 */
    private function formatTime(?\DateTimeInterface $datetime): string
    {
        if (is_null($datetime)) {
            return '--:--:--';
        }

        return $datetime->format('H:i:s');
    }

    /** 休憩時間や勤務時間を表示形式に変換する。 */
    private function formatSeconds(?int $seconds): string
    {
        if (is_null($seconds)) {
            return '--:--:--';
        }

        return CarbonImmutable::create()->addSeconds($seconds)->format('H:i:s');
    }
}

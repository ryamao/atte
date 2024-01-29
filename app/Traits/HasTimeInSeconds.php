<?php

namespace App\Traits;

use Carbon\CarbonImmutable;

trait HasTimeInSeconds
{
    /** 時間を秒数で返す。終了せずに日付を跨いだ場合の時間では null を返す。 */
    public function timeInSeconds(): ?int
    {
        if (is_null($this->ended_at)) return null;

        $begunAt = CarbonImmutable::make($this->begun_at);
        $endedAt = CarbonImmutable::make($this->ended_at);
        return $begunAt->diffInSeconds($endedAt);
    }
}

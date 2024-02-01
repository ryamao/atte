<?php

declare(strict_types=1);

namespace App\Traits;

trait HasTimeInSeconds
{
    /** 時間を秒数で返す。終了せずに日付を跨いだ場合の時間では null を返す。 */
    public function timeInSeconds(): ?int
    {
        if (is_null($this->ended_at)) {
            return null;
        }

        return $this->begun_at->diffInSeconds($this->ended_at);
    }
}

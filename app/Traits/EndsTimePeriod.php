<?php

namespace App\Traits;

use App\Models\BreakBegin;
use App\Models\ShiftBegin;
use DateTimeInterface;

trait EndsTimePeriod
{
    public static function endPeriod(ShiftBegin|BreakBegin $begin, ?DateTimeInterface $now): void
    {
        static::create([
            'user_id' => $begin->user_id,
            'begun_at' => $begin->begun_at,
            'ended_at' => $now,
        ]);
    }
}

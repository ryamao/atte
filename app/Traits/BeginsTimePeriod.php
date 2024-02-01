<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\User;
use DateTimeInterface;

trait BeginsTimePeriod
{
    public static function beginPeriod(User $user, DateTimeInterface $now): void
    {
        static::firstOrCreate(
            ['user_id' => $user->id],
            ['begun_at' => $now],
        );
    }
}

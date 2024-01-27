<?php

declare(strict_types=1);

namespace App;

use App\Models\BreakBegin;
use App\Models\ShiftBegin;
use App\Models\User;
use DateTimeInterface;

/** 会員の勤務状態と休憩状態を表す列挙型 */
enum WorkStatus
{
    /** `勤務開始前 || 勤務終了後` */
    case Before;
    /** `勤務開始後 && (休憩開始前 || 休憩終了後)` */
    case During;
    /** `勤務開始後 && 休憩開始後` */
    case Break;

    /** 現在日時における会員の状態を取得する。 */
    public static function ask(User $user, DateTimeInterface $now): self
    {
        $breakBegins = BreakBegin::currentBreak($user, $now);
        if ($breakBegins->exists()) return self::Break;

        $shiftBegins = ShiftBegin::currentShift($user, $now);
        if ($shiftBegins->exists()) return self::During;

        return self::Before;
    }
}

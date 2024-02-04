<?php

declare(strict_types=1);

namespace App;

/** 会員の勤務状況を表す列挙型 */
enum WorkStatus
{
    /** `勤務開始前 || 勤務終了後` */
    case Before;
    /** `勤務開始後 && (休憩開始前 || 休憩終了後)` */
    case During;
    /** `勤務開始後 && 休憩開始後` */
    case Break;
    /** 勤務状況が取得できない場合 */
    case Error;

    /** 勤務中であることを判定する */
    public function isDuring(): bool
    {
        return $this === self::During;
    }

    /** 休憩中であることを判定する */
    public function isBreak(): bool
    {
        return $this === self::Break;
    }

    /** 勤務中でも休憩中でもないことを判定する */
    public function isBefore(): bool
    {
        return $this === self::Before;
    }
}

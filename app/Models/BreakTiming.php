<?php

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** 確定済みの休憩時間を表すモデル */
class BreakTiming extends Model
{
    use HasFactory;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at', 'ended_at'];

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 休憩終了を記録する。 */
    public static function endBreak(BreakBegin $breakBegin, DateTimeInterface $now): void
    {
        static::create([
            'user_id' => $breakBegin->user_id,
            'begun_at' => $breakBegin->begun_at,
            'ended_at' => $now,
        ]);
    }
}

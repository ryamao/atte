<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftTiming extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'begun_at', 'ended_at'];

    /** created_atとupdated_atの自動更新をオミットする。 */
    public $timestamps = false;

    /** 勤務終了処理を行う。 */
    public static function endShift(ShiftBegin $shiftBegin, ?DateTimeInterface $now): void
    {
        ShiftTiming::create([
            'user_id' => $shiftBegin->user_id,
            'begun_at' => $shiftBegin->begun_at,
            'ended_at' => $now,
        ]);
    }

    /**
     * 勤務再開処理のために ShiftTiming を削除して勤務開始日時を返す。
     * 指定のユーザ、指定の年月日に ShiftTiming が存在しない場合 null を返す。
     */
    public static function cancelShift(User $user, DateTimeInterface $today): ?DateTimeInterface
    {
        $shiftTiming = ShiftTiming
            ::where('user_id', $user->id)
            ->whereDate('begun_at', $today)->first();

        if (is_null($shiftTiming)) return null;

        $begunAt = $shiftTiming->begun_at;
        $shiftTiming->delete();
        return CarbonImmutable::parse($begunAt, $today->getTimezone());
    }
}

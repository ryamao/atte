<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EndsTimePeriod;
use App\Traits\HasTimeInSeconds;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** 確定済みの勤務時間を表すモデル */
class ShiftTiming extends Model
{
    use HasFactory;
    use EndsTimePeriod;
    use HasTimeInSeconds;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at', 'ended_at'];

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /**
     * 勤務再開処理のために ShiftTiming を削除して勤務開始日時を返す。
     * 指定のユーザ、指定の年月日に ShiftTiming が存在しない場合 null を返す。
     */
    public static function cancelShift(User $user, DateTimeInterface $today): ?DateTimeInterface
    {
        $shiftTiming = static
            ::where('user_id', $user->id)
            ->whereDate('begun_at', $today)->first();

        if (is_null($shiftTiming)) return null;

        $begunAt = $shiftTiming->begun_at;
        $shiftTiming->delete();
        return CarbonImmutable::parse($begunAt, $today->getTimezone());
    }

    /** 打刻を行った会員を取得する。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

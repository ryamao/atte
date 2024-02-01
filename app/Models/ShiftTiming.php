<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EndsTimePeriod;
use App\Traits\HasTimeInSeconds;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 確定済みの勤務時間を表すリソースエンティティ
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\CarbonImmutable $begun_at
 * @property \Illuminate\Support\CarbonImmutable $ended_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder begunAtDate(\App\Models\User $user, \DateTimeInterface $date)
 */
class ShiftTiming extends Model
{
    use EndsTimePeriod;
    use HasFactory;
    use HasTimeInSeconds;

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at', 'ended_at'];

    /** 日付キャストの定義。 */
    protected $casts = [
        'begun_at' => 'immutable_date:Y-m-d H:i:s',
        'ended_at' => 'immutable_date:Y-m-d H:i:s',
    ];

    /** 特定会員かつ指定の日付の勤務のみ含むスコープを適用する。 */
    public function scopeBegunAtDate(Builder $query, User $user, DateTimeInterface $date): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', $date);
    }

    /** 打刻を行った会員を取得する。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

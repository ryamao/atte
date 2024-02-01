<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BeginsTimePeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 勤務開始を表すイベントエンティティ
 *
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\CarbonImmutable $begun_at
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder currentShift(\App\Models\User $user, \DateTimeInterface $today)
 * @method static \Illuminate\Database\Eloquent\Builder previousShift(\App\Models\User $user, \DateTimeInterface $today)
 */
class ShiftBegin extends Model
{
    use BeginsTimePeriod;
    use HasFactory;

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at'];

    /** 日付キャストの定義。 */
    protected $casts = [
        'begun_at' => 'immutable_date:Y-m-d H:i:s',
    ];

    /** 打刻を行った会員を取得する。 */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** 特定会員の当日の勤務のみ含むスコープを適用する。 */
    public function scopeCurrentShift(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', $today);
    }

    /** 特定会員の前日以前の勤務のみ含むスコープを適用する。 */
    public function scopePreviousShift(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', '<', $today);
    }
}

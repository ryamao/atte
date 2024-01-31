<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\BeginsTimePeriod;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** 休憩開始イベントを表すモデル */
class BreakBegin extends Model
{
    use HasFactory;
    use BeginsTimePeriod;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at'];

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 特定ユーザの当日の休憩のみ含むスコープを適用する。 */
    public function scopeCurrentBreak(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', $today);
    }

    /** 特定ユーザの前日以前の休憩のみ含むスコープを適用する。 */
    public function scopePreviousBreak(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', '<', $today);
    }
}

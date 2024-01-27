<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** 勤務開始イベントを表すモデル */
class ShiftBegin extends Model
{
    use HasFactory;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at'];

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 勤務開始を記録する。同一ユーザが勤務中の場合は何もしない。 */
    public static function beginShift(User $user, DateTimeInterface $now): void
    {
        static::firstOrCreate(
            ['user_id' => $user->id],
            ['begun_at' => $now],
        );
    }

    /** 特定ユーザの当日の勤務のみ含むスコープを適用する。 */
    public function scopeCurrentShift(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', $today);
    }

    /** 特定ユーザの前日以前の勤務のみ含むスコープを適用する。 */
    public function scopePreviousShift(Builder $query, User $user, DateTimeInterface $today): void
    {
        $query->where('user_id', $user->id);
        $query->whereDate('begun_at', '<', $today);
    }

    /** エンティティとして比較する。テストで使用する。 */
    public function equals(ShiftBegin $shiftBegin): bool
    {
        return $this->id === $shiftBegin->id;
    }
}

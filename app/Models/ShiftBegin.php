<?php

declare(strict_types=1);

namespace App\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftBegin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'begun_at'];

    /** created_atとupdated_atの自動更新をオミットする。 */
    public $timestamps = false;

    /** 勤務開始前に限り勤務開始処理を行う。 */
    public static function beginShift(User $user, DateTimeInterface $now): void
    {
        ShiftBegin::firstOrCreate(
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

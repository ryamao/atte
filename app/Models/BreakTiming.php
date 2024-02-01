<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EndsTimePeriod;
use App\Traits\HasTimeInSeconds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 確定済みの休憩時間を表すリソースエンティティ
 * 
 * @property int $id
 * @property int $user_id
 * @property \Illuminate\Support\CarbonImmutable $begun_at
 * @property \Illuminate\Support\CarbonImmutable $ended_at
 */
class BreakTiming extends Model
{
    use HasFactory;
    use EndsTimePeriod;
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
}

<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\EndsTimePeriod;
use App\Traits\HasTimeInSeconds;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/** 確定済みの休憩時間を表すモデル */
class BreakTiming extends Model
{
    use HasFactory;
    use EndsTimePeriod;
    use HasTimeInSeconds;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['user_id', 'begun_at', 'ended_at'];

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;
}

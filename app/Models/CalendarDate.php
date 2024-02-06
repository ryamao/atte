<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * このテーブルは、指定年月内の日付を保存するためのテンポラリテーブルである。
 * 会員別の勤怠情報を取得するサービスで使用する。
 *
 * @property int $id
 * @property \Illuminate\Support\CarbonImmutable $date
 */
class CalendarDate extends Model
{
    use HasFactory;

    /** created_atとupdated_atの自動更新を解除する。 */
    public $timestamps = false;

    /** 一括割り当て可能な属性。 */
    protected $fillable = ['date'];

    /** 日付キャストの定義。 */
    protected $casts = [
        'date' => 'immutable_date:Y-m-d',
    ];
}

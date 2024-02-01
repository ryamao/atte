<?php

namespace App\Models;

use App\WorkStatus;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * @return HasOne<ShiftBegin>
     */
    public function shiftBegin(): HasOne
    {
        return $this->hasOne(ShiftBegin::class);
    }

    /**
     * @return HasMany<ShiftTiming>
     */
    public function shiftTimings(): HasMany
    {
        return $this->hasMany(ShiftTiming::class);
    }

    /**
     * @return HasOne<BreakBegin>
     */
    public function breakBegin(): HasOne
    {
        return $this->hasOne(BreakBegin::class);
    }

    /**
     * @return HasMany<BreakTiming>
     */
    public function breakTimings(): HasMany
    {
        return $this->hasMany(BreakTiming::class);
    }

    /** ある日に勤務しているかどうか判定する */
    public function isWorkingOn(DateTimeInterface $date): bool
    {
        return $this->shiftBegin()->whereDate('begun_at', $date)->exists()
            || $this->shiftTimings()->whereDate('begun_at', $date)->exists();
    }

    /** ある日の勤務開始日時を取得する */
    public function shiftBegunAtDate(DateTimeInterface $date): ?DateTimeInterface
    {
        $shiftBegin = $this->shiftBegin()->whereDate('begun_at', $date)->first();
        if ($shiftBegin) {
            return CarbonImmutable::make($shiftBegin->begun_at);
        }

        $shiftTiming = $this->shiftTimings()->whereDate('begun_at', $date)->first();
        return CarbonImmutable::make($shiftTiming?->begun_at);
    }

    /** ある日の勤務終了日時を取得する */
    public function shiftEndedAtDate(DateTimeInterface $date): ?DateTimeInterface
    {
        $shiftTiming = $this->shiftTimings()->whereDate('begun_at', $date)->first();
        return CarbonImmutable::make($shiftTiming?->ended_at);
    }

    /** ある日の休憩時間を秒数で取得する */
    public function breakTimeInSeconds(DateTimeInterface $date): ?int
    {
        $breakBegin = $this->breakBegin()->whereDate('begun_at', $date)->first();
        if ($breakBegin) return null;

        if ($this->breakTimings()->whereNull('ended_at')->exists()) return null;

        $breakTimings = $this->breakTimings()->whereDate('begun_at', $date)->get();
        return $breakTimings->sum(fn (BreakTiming $breakTiming) => $breakTiming->timeInSeconds());
    }

    /** ある日の勤務時間を秒数で取得する */
    public function shiftTimeInSeconds(DateTimeInterface $date): ?int
    {
        $shiftBegin = $this->shiftBegin()->whereDate('begun_at', $date)->first();
        if ($shiftBegin) return null;

        $shiftTiming = $this->shiftTimings()->whereDate('begun_at', $date)->first();
        if (is_null($shiftTiming)) return 0;

        if (is_null($shiftTiming->ended_at)) return null;

        return $shiftTiming->timeInSeconds();
    }

    /** ある日の労働時間を秒数で取得する */
    public function workTimeInSeconds(DateTimeInterface $date): ?int
    {
        $shiftTime = $this->shiftTimeInSeconds($date);
        if (is_null($shiftTime)) return null;

        $breakTime = $this->breakTimeInSeconds($date);
        if (is_null($breakTime)) return null;

        return $shiftTime - $breakTime;
    }

    /** ある日の勤務状況を取得する */
    public function workStatus(DateTimeInterface $date): WorkStatus
    {
        if ($this->breakBegin()->whereDate('begun_at', $date)->exists()) return WorkStatus::Break;
        if ($this->shiftBegin()->whereDate('begun_at', $date)->exists()) return WorkStatus::During;
        return WorkStatus::Before;
    }
}

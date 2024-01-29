<?php

namespace App\Models;

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
     * @return HasMany<ShiftBegin>
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
}

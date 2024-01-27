<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakBegin extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'begun_at'];

    public $timestamps = false;
}

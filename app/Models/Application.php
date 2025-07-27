<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Application extends Model
{
    protected $fillable = [
        'user_id',
        'attendance_id',
        'reason',
        'status',
        'fixed_clock_in',
        'fixed_clock_out',
        'fixed_break_start',
        'fixed_break_end',
    ];

    protected $casts = [
        'fixed_clock_in' => 'datetime:H:i',
        'fixed_clock_out' => 'datetime:H:i',
        'fixed_break_start' => 'datetime:H:i',
        'fixed_break_end' => 'datetime:H:i',
        'fixed_breaks' => 'array',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
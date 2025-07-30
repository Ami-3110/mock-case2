<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectRequest extends Model
{
    protected $table = 'attendance_correct_requests';
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
        'fixed_clock_in' => 'datetime',
        'fixed_clock_out' => 'datetime',
        'fixed_break_start' => 'datetime',
        'fixed_break_end' => 'datetime',
        'fixed_breaks' => 'array',
    ];

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => '承認待ち',
            'approved' => '承認済み',
            default => $this->status,
        };
    }

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

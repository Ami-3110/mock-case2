<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AttendanceApplication extends Model
{
    protected $fillable = [
        'attendance_id',
        'reason',
        'is_approved',
        'approved_by',
        'approved_at',
    ];
    
    protected $casts = [
        'approved_at' => 'datetime',
    ];

    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

}
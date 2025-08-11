<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\CarbonInterval;
use App\Models\AttendanceCorrectRequest;

class Attendance extends Model
{
    // === 1. 基本設定 ===
    protected $fillable = [
        'user_id',
        'work_date',
        'clock_in',
        'clock_out',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'work_date' => 'date',
    ];

    // === 2. リレーション ===
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function attendanceCorrectRequests()
    {
        return $this->hasMany(AttendanceCorrectRequest::class);
    }

    public function latestAttendanceCorrectRequest()
    {
        return $this->hasOne(AttendanceCorrectRequest::class)->latestOfMany();
    }

    // === 3. アクセサ ===
    public function getStatusAttribute()
    {
        if (!$this->clock_in) {
            return '勤務外';
        }
        if ($this->clock_out) {
            return '退勤済';
        }
        if ($this->breakTimes()->whereNull('break_end')->exists()) {
            return '休憩中';
        }
        return '勤務中';
    }

    public function getTotalBreakDurationAttribute()
    {
        $total = CarbonInterval::seconds(0);
        foreach ($this->breakTimes as $break) {
            if ($break->break_start && $break->break_end) {
                $interval = $break->break_start->diffAsCarbonInterval($break->break_end);
                $total = $total->add($interval);
            }
        }

        return $total;
    }

    public function getWorkDurationAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return null;
        }
        $workSeconds = $this->clock_in->diffInSeconds($this->clock_out);
        $breakInterval = $this->total_break_duration;
        $breakSeconds = $breakInterval instanceof CarbonInterval
            ? $breakInterval->totalSeconds
            : 0;
        $actualWorkSeconds = max(0, $workSeconds - $breakSeconds);

        return CarbonInterval::seconds($actualWorkSeconds);
    }

    public function getWorkTimeAttribute()
    {
        return $this->formatInterval($this->work_duration);
    }

    public function getBreakTimeAttribute()
    {
        return $this->formatInterval($this->total_break_duration);
    }

    // === 4. スコープ ===
    public function scopeTodayByUser($query, $userId)
    {
        return $query->where('user_id', $userId)
            ->whereDate('work_date', now()->toDateString())
            ->latest();
    }


    // === 5. ユーティリティ ===
    protected function formatInterval(?CarbonInterval $interval): string
    {
        if (!$interval instanceof CarbonInterval) {
            return '-';
        }
        $hours = floor($interval->totalSeconds / 3600);
        $minutes = floor(($interval->totalSeconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }
    
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Support\Facades\Log;

class Attendance extends Model
{
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

    protected $appends = [
        'work_duration',
        'total_break_duration',
    ];

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

    public function getTotalBreakDurationFormattedAttribute()
    {
        $interval = $this->total_break_duration;
    
        if (!$interval instanceof CarbonInterval) {
            return '-';
        }
    
        $totalSeconds = $interval->total('seconds');
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
    
        return sprintf('%d:%02d', $hours, $minutes);
    }

    public function getWorkDurationFormattedAttribute()
    {
        $interval = $this->work_duration;

        if (!$interval instanceof CarbonInterval) {
            return '-';
        }

        $totalSeconds = $interval->total('seconds');
        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);

        return sprintf('%d:%02d', $hours, $minutes);
    }


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function breakTimes()
    {
        return $this->hasMany(BreakTime::class, 'attendance_id');
    }

    public function application()
    {
        return $this->hasOne(Application::class);
    }
}
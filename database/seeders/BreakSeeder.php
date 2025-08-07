<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Carbon;

class BreakSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            $numBreaks = rand(1, 2);
            $existingSlots = [];

            for ($i = 0; $i < $numBreaks; $i++) {
                $breakStart = $this->randomBreakTime($attendance->clock_in, $attendance->clock_out, $existingSlots);
                $duration = rand(15, 60); 

                $breakEnd = (clone $breakStart)->addMinutes($duration);

                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);

                $existingSlots[] = [$breakStart, $breakEnd];
            }
        }
    }

    private function randomBreakTime($start, $end, $slots = [])
    {
        $start = Carbon::parse($start)->copy()->addHour();
        $end = Carbon::parse($end)->copy()->subHour();

        $maxAttempts = 10;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $minute = rand(0, $start->diffInMinutes($end));
            $candidate = $start->copy()->addMinutes($minute);

            $conflict = false;
            foreach ($slots as [$s, $e]) {
                if ($candidate->between($s, $e)) {
                    $conflict = true;
                    break;
                }
            }

            if (!$conflict) {
                return $candidate;
            }
        }

        return $start;
    }
}

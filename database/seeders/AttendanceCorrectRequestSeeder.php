<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AttendanceCorrectRequestSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            '電車遅延のため', '寝坊しました', '病院に寄った', '通院のため',
            '役所に寄った', '体調不良', '面談のため', '家庭の事情',
            '会議の延長', '忘れてました',
        ];

        $targetAttendances = Attendance::inRandomOrder()->limit(25)->get();

        foreach ($targetAttendances as $index => $attendance) {
            // 修正後の出勤時間（±30分ランダム）
            $fixedIn = Carbon::parse($attendance->clock_in)->copy()->addMinutes(rand(-30, 30));
            $breaks = $this->generateBreaks($fixedIn);
            $breakTotal = collect($breaks)->reduce(function ($carry, $b) {
                $start = Carbon::parse($b['break_start']);
                $end = Carbon::parse($b['break_end']);
                return $carry + $start->diffInMinutes($end);
            }, 0);

            $fixedOut = $fixedIn->copy()->addHours(8)->addMinutes($breakTotal);

            AttendanceCorrectRequest::create([
                'user_id' => $attendance->user_id,
                'attendance_id' => $attendance->id,
                'reason' => $reasons[array_rand($reasons)],
                'status' => $index < 20 ? 'pending' : 'approved',
                'fixed_clock_in' => $fixedIn,
                'fixed_clock_out' => $fixedOut,
                'fixed_breaks' => $breaks,
            ]);
        }
    }

    private function generateBreaks(Carbon $baseTime)
    {
        $numBreaks = rand(1, 2);
        $breaks = [];

        for ($i = 0; $i < $numBreaks; $i++) {
            $start = $baseTime->copy()->addHours(rand(2, 5))->addMinutes(rand(0, 30));
            $duration = rand(15, 60);
            $end = $start->copy()->addMinutes($duration);

            $breaks[] = [
                'break_start' => $start->format('H:i'),
                'break_end' => $end->format('H:i'),
            ];
        }

        return $breaks;
    }

}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run()
    {
        $faker = Faker::create('ja_JP');

        foreach (range(1, 30) as $i) {
            $workDate = Carbon::now()->subDays($i)->startOfDay();

            $clockIn = $workDate->copy()->setTime(9, 0, 0);
            $clockOut = $workDate->copy()->setTime(18, 0, 0);

            $attendance = Attendance::create([
                'user_id' => 1,
                'work_date' => $workDate->toDateString(),
                'clock_in' => $clockIn,
                'clock_out' => $clockOut,
            ]);

            // ランダムな休憩を1時間（11:30〜12:30〜13:30あたりで変動）
            $breakStart = $faker->dateTimeBetween($workDate->copy()->setTime(11, 30), $workDate->copy()->setTime(12, 30));
            $breakEnd = (clone $breakStart)->modify('+1 hour');

            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => $breakStart,
                'break_end' => $breakEnd,
            ]);
        }
    }
}

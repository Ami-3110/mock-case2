<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Faker\Factory as Faker;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        $start = Carbon::create(2025, 6, 1);
        $end = Carbon::create(2025, 10, 31);

        $users = User::whereIn('email', [
            'test@example.com',
            'reina.n@coachtech.com',
            'taro.y@coachtech.com',
            'issei.m@coachtech.com',
            'keikichi.y@coachtech.com',
            'tomomi.a@coachtech.com',
            'norio.n@coachtech.com',
        ])->get();

        foreach ($users as $user) {
            $current = $start->copy();
            while ($current <= $end) {
                if ($current->isWeekday()) {
                    $clockIn = $current->copy()->setTime(rand(8, 10), rand(0, 1) ? 0 : 30);

                    $breakMinutes = rand(30, 90); 
                    $clockOut = $clockIn->copy()->addHours(8)->addMinutes($breakMinutes);

                    Attendance::create([
                        'user_id' => $user->id,
                        'work_date' => $current->toDateString(),
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                    ]);
                }

                $current->addDay();
            }
        }
    }

}

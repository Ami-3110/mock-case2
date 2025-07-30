<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        // まず10人のユーザーを作成（うち1人はすでに存在するtest@example.com）
        $users = collect();

        $users->push(
            User::updateOrCreate(
                ['email' => 'test@example.com'],
                [
                    'name' => 'Test User',
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                ]
            )
        );

        for ($i = 1; $i <= 9; $i++) {
            $users->push(
                User::factory()->create([
                    'name' => "Staff {$i}",
                    'email' => "staff{$i}@example.com",
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_admin' => false,
                ])
            );
        }

        // 勤怠データを3ヶ月分作成
        foreach ($users as $user) {
            foreach (range(1, 90) as $dayOffset) {
                $date = Carbon::now()->subDays($dayOffset)->startOfDay();

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date,
                    'clock_in' => $date->copy()->setTime(9, 0),
                    'clock_out' => $date->copy()->setTime(18, 0),
                ]);

                // 各勤怠に1回の休憩を付ける（breaks テーブルに）
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $date->copy()->setTime(12, 0),
                    'break_end' => $date->copy()->setTime(13, 0),
                ]);
            }
        }

        // 修正申請を10件（ランダムなユーザー・日付で）
        foreach (range(1, 10) as $i) {
            $user = $users->random();
            $workDate = Carbon::now()->subDays(rand(1, 90))->toDateString();

            AttendanceCorrectRequest::create([
                'user_id' => 3,
                'attendance_id' => 5, // 対象となるattendanceのID
                'reason' => '出勤時間を修正したい',
                'status' => 'pending',
                'fixed_clock_in' => '2025-07-29 09:00:00',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $users = collect();

        // テストユーザー（メールアドレス固定）
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

        // スタッフユーザーを9人追加（合計10人）
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

        // 勤怠データと2回の休憩を3ヶ月分作成
        foreach ($users as $user) {
            foreach (range(1, 90) as $dayOffset) {
                $date = Carbon::now()->subDays($dayOffset)->startOfDay();

                $attendance = Attendance::create([
                    'user_id' => $user->id,
                    'work_date' => $date,
                    'clock_in' => $date->copy()->setTime(9, 0),
                    'clock_out' => $date->copy()->setTime(18, 0),
                ]);

                // 2回の休憩時間を追加（例：昼休憩＋午後の小休憩）
                $attendance->breakTimes()->createMany([
                    [
                        'break_start' => $date->copy()->setTime(12, 0),
                        'break_end'   => $date->copy()->setTime(13, 0),
                    ],
                    [
                        'break_start' => $date->copy()->setTime(15, 30),
                        'break_end'   => $date->copy()->setTime(15, 45),
                    ],
                ]);
            }
        }

        // 修正申請（※サンプル：ユーザーID/勤怠IDは適当に固定）
        foreach (range(1, 10) as $i) {
            $user = $users->random();
            $attendance = Attendance::where('user_id', $user->id)->inRandomOrder()->first();

            if ($attendance) {
                AttendanceCorrectRequest::create([
                    'user_id' => $user->id,
                    'attendance_id' => $attendance->id,
                    'reason' => '出勤時間を修正したい',
                    'status' => 'pending',
                    'fixed_clock_in' => $attendance->clock_in->copy()->subMinutes(10),
                ]);
            }
        }
    }
}

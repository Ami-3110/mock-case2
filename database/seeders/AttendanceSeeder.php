<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // 期間：2025/5/1〜2025/8/14（平日のみ）
        $start = Carbon::create(2025, 5, 1);
        $end   = Carbon::create(2025, 8, 14);

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

            while ($current->lte($end)) {
                if ($current->isWeekday()) {
                    // 出勤時刻：8:00 / 8:30 / 9:00 / 9:30 / 10:00 / 10:30 のいずれか
                    $clockIn = $current->copy()->setTime(rand(8, 10), rand(0, 1) ? 0 : 30);

                    // 休憩合計（分）：30〜90分
                    $breakMinutes = rand(30, 90);

                    // 退勤：勤務8時間 + 休憩
                    $clockOut = $clockIn->copy()->addHours(8)->addMinutes($breakMinutes);

                    // 同じ user_id × work_date があれば更新、なければ作成
                    Attendance::updateOrCreate(
                        [
                            'user_id'   => $user->id,
                            'work_date' => $current->toDateString(),
                        ],
                        [
                            'clock_in'  => $clockIn,
                            'clock_out' => $clockOut,
                        ]
                    );
                }

                $current->addDay(); // 翌日へ
            }
        }
    }
}

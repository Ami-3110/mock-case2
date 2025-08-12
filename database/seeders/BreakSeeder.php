<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class BreakSeeder extends Seeder
{
    public function run(): void
    {
        $attendances = Attendance::all();

        foreach ($attendances as $attendance) {
            // 同じ勤怠の既存休憩を削除（再シード時の重複防止）
            $attendance->breakTimes()->delete();

            $numBreaks = rand(1, 2);
            $slots = []; // 既存の休憩スロット（[start, end]）

            // 休憩を1〜2件作成
            for ($i = 0; $i < $numBreaks; $i++) {
                $slot = $this->makeNonOverlappingBreakSlot($attendance->clock_in, $attendance->clock_out, $slots);

                if ($slot) {
                    [$breakStart, $breakEnd] = $slot;

                    BreakTime::create([
                        'attendance_id' => $attendance->id,
                        'break_start'   => $breakStart,
                        'break_end'     => $breakEnd,
                    ]);

                    $slots[] = [$breakStart, $breakEnd];
                }
            }
        }
    }

    /**
     * 勤務時間のうち [clock_in+60min, clock_out-60min] の範囲で
     * 既存スロットと重ならない休憩スロットを生成して返す。
     * 見つからなければ null を返す。
     */
    private function makeNonOverlappingBreakSlot($clockIn, $clockOut, array $existingSlots): ?array
    {
        $winStart = Carbon::parse($clockIn)->copy()->addMinutes(60);
        $winEnd   = Carbon::parse($clockOut)->copy()->subMinutes(60);

        // そもそも休憩を入れられる余地がなければスキップ
        if ($winEnd->lte($winStart)) {
            return null;
        }

        $maxAttempts = 20;
        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $duration = rand(15, 60); // 15〜60分

            // duration を考慮した開始時刻の上限
            $latestStart = $winEnd->copy()->subMinutes($duration);
            if ($latestStart->lt($winStart)) {
                // 窓が狭すぎる場合は duration を短くして再挑戦
                $duration = max(5, $winEnd->diffInMinutes($winStart) - 1);
                if ($duration < 5) {
                    return null;
                }
                $latestStart = $winEnd->copy()->subMinutes($duration);
            }

            $offset = rand(0, $winStart->diffInMinutes($latestStart));
            $start  = $winStart->copy()->addMinutes($offset)->second(0);
            $end    = $start->copy()->addMinutes($duration)->second(0);

            if ($this->isNonOverlapping($start, $end, $existingSlots)) {
                return [$start, $end];
            }
        }

        return null;
    }

    /**
     * 既存スロットと時間帯が重ならないか判定
     */
    private function isNonOverlapping(Carbon $start, Carbon $end, array $slots): bool
    {
        foreach ($slots as [$s, $e]) {
            // 重ならない条件：新しい区間が完全に左 or 右
            // 重なる条件： !(end <= s || start >= e)
            if (!($end->lte($s) || $start->gte($e))) {
                return false;
            }
        }
        return true;
    }
}

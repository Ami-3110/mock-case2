<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AttendanceCorrectRequestSeeder extends Seeder
{
    public function run(): void
    {
        $reasons = [
            '電車遅延のため', '寝坊しました', '病院に寄った', '通院のため',
            '役所に寄った', '体調不良', '面談のため', '家庭の事情',
            '会議の延長', '忘れてました',
        ];

        // 期間を明示（AttendanceSeeder に合わせる）
        $rangeStart = Carbon::create(2025, 5, 1)->startOfDay();
        $rangeEnd   = Carbon::create(2025, 8, 14)->endOfDay();

        // 対象期間の勤怠からランダム25件
        $targets = Attendance::whereBetween('work_date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->inRandomOrder()
            ->limit(25)
            ->get();

        foreach ($targets as $index => $attendance) {
            // 同じ勤怠の既存申請を削除して重複防止
            $attendance->attendanceCorrectRequests()->delete();

            // 修正後の出勤（±30分）
            $fixedIn = Carbon::parse($attendance->clock_in)->copy()->addMinutes(rand(-30, 30))->second(0);

            // 休憩（1〜2本, 15〜60分, 重複なし, 勤務開始+1h 〜 勤務終了-1h の範囲）
            [$breaks, $breakTotal] = $this->generateBreaksForFixedIn($fixedIn);

            // 修正後の退勤：8h労働 + 休憩合計
            $fixedOut = $fixedIn->copy()->addHours(8)->addMinutes($breakTotal)->second(0);

            AttendanceCorrectRequest::create([
                'user_id'          => $attendance->user_id,
                'attendance_id'    => $attendance->id,
                'reason'           => $reasons[array_rand($reasons)],
                'status'           => $index < 20 ? 'pending' : 'approved', // 20:5 の比率
                'fixed_clock_in'   => $fixedIn,
                'fixed_clock_out'  => $fixedOut,
                'fixed_breaks'     => $breaks, // JSON（配列）で保存
            ]);
        }
    }

    /**
     * fixedIn を基準に 1〜2本の休憩スロットを作る。
     * 休憩は [fixedIn+60min, fixedIn+8h-60min] の範囲で、重複なし。
     * 返り値: [breaks(array), breakTotalMinutes(int)]
     */
    private function generateBreaksForFixedIn(Carbon $fixedIn): array
    {
        $winStart = $fixedIn->copy()->addMinutes(60);
        $winEnd   = $fixedIn->copy()->addHours(8)->subMinutes(60);

        // 休憩を入れられる余地がない場合は0本
        if ($winEnd->lte($winStart)) {
            return [[], 0];
        }

        $num = rand(1, 2);
        $slots = [];
        $breaks = [];

        for ($i = 0; $i < $num; $i++) {
            $slot = $this->pickNonOverlapSlot($winStart, $winEnd, $slots);
            if (!$slot) break;

            [$start, $end] = $slot;
            $slots[] = [$start, $end];

            $breaks[] = [
                'break_start' => $start->format('H:i'),
                'break_end'   => $end->format('H:i'),
            ];
        }

        // 合計休憩（分）
        $total = array_reduce($slots, function (int $carry, array $s) {
            /** @var Carbon $st */
            /** @var Carbon $en */
            [$st, $en] = $s;
            return $carry + $st->diffInMinutes($en);
        }, 0);

        return [$breaks, $total];
    }

    /**
     * 窓 [winStart, winEnd] 内で重ならない1本の休憩スロットを選ぶ。
     * 15〜60分。見つからなければ null。
     */
    private function pickNonOverlapSlot(Carbon $winStart, Carbon $winEnd, array $existing): ?array
    {
        $maxAttempts = 20;

        for ($attempt = 0; $attempt < $maxAttempts; $attempt++) {
            $duration = rand(15, 60);

            // duration を考慮した開始の上限
            $latestStart = $winEnd->copy()->subMinutes($duration);
            if ($latestStart->lt($winStart)) {
                // 窓が狭すぎる場合は duration を詰める
                $duration = max(5, $winEnd->diffInMinutes($winStart) - 1);
                if ($duration < 5) return null;
                $latestStart = $winEnd->copy()->subMinutes($duration);
            }

            $offset = rand(0, $winStart->diffInMinutes($latestStart));
            $start  = $winStart->copy()->addMinutes($offset)->second(0);
            $end    = $start->copy()->addMinutes($duration)->second(0);

            if ($this->nonOverlapping($start, $end, $existing)) {
                return [$start, $end];
            }
        }

        return null;
    }

    private function nonOverlapping(Carbon $start, Carbon $end, array $slots): bool
    {
        foreach ($slots as [$s, $e]) {
            // 重なる条件： !(end <= s || start >= e)
            if (!($end->lte($s) || $start->gte($e))) {
                return false;
            }
        }
        return true;
    }
}

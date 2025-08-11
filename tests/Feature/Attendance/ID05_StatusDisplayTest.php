<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ID05 ステータス表示
 *
 * テスト内容（シート準拠）：
 * - 勤務外の場合、勤怠ステータスが「勤務外」
 * - 出勤中の場合、勤怠ステータスが「出勤中」
 * - 休憩中の場合、勤怠ステータスが「休憩中」
 * - 退勤済の場合、勤怠ステータスが「退勤済」
 */
class ID05_StatusDisplayTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    public function test_勤務外の場合ステータスが勤務外(): void
    {
        $this->loginUser();

        $res = $this->get(route('attendance.index'));
        $res->assertOk()->assertSee('勤務外');
    }

    public function test_出勤中の場合ステータスが出勤中(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now(),
            'clock_out' => null,
        ]);

        $res = $this->get(route('attendance.index'));
        $res->assertOk()->assertSee('出勤中');
    }

    public function test_休憩中の場合ステータスが休憩中(): void
    {
        $user = $this->loginUser();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now()->subHour(),
            'clock_out' => null,
        ]);

        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => now()->subMinutes(10),
            'break_end'     => null,
        ]);

        $res = $this->get(route('attendance.index'));
        $res->assertOk()->assertSee('休憩中');
    }

    public function test_退勤済の場合ステータスが退勤済(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => now()->toDateString(),
            'clock_in'  => now()->subHours(8),
            'clock_out' => now(),
        ]);

        $res = $this->get(route('attendance.index'));
        $res->assertOk()->assertSee('退勤済');
    }
}

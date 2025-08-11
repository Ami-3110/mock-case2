<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID06 出勤機能
 *
 * テスト内容（シート準拠）：
 * - 出勤ボタンが正しく機能する
 * - 出勤は一日一回のみできる
 * - 出勤時刻が勤怠一覧画面で確認できる
 */
class ID06_ClockInTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        return $u;
    }

    public function test_出勤ボタンが正しく機能する(): void
    {
        $user = $this->loginUser();

        $res = $this->post(route('attendance.start'));
        $res->assertRedirect(route('attendance.index'));

        $this->assertDatabaseHas('attendances', [
            'user_id'   => $user->id,
            'work_date' => now()->toDateString(),
        ]);
        $this->assertNotNull(Attendance::first()->clock_in);
    }

    public function test_出勤は一日一回のみできる(): void
    {
        $user = $this->loginUser();

        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));

        $this->post(route('attendance.start'))->assertRedirect();

        $count = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', now()->toDateString())
            ->count();

        $this->assertSame(1, $count);
    }

    public function test_出勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->loginUser();

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 17, 27, 0));

        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));
        $this->post(route('attendance.end'))->assertRedirect(route('attendance.index'));

        $attendance = Attendance::first();
        $expected   = $attendance->clock_in->format('H:i');

        $res = $this->get(route('attendance.list', [
            'year'  => now()->year,
            'month' => now()->format('m'),
        ]))->assertOk();

        $res->assertSee($attendance->work_date->format('m/d'));
        $res->assertSee($expected);
    }
}

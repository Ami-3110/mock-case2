<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID08 退勤機能
 *
 * テスト内容（シート準拠）：
 * - 退勤ボタンが正しく機能する
 * - 退勤時刻が勤怠一覧で確認できる
 */
class ID08_ClockOutTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        return $u;
    }

    public function test_退勤できてステータスが退勤済になる(): void
    {
        $this->login();

        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));
        $this->post(route('attendance.end'))->assertRedirect(route('attendance.index'));

        $attendance = Attendance::first();
        $this->assertNotNull($attendance->clock_out);

        $this->get(route('attendance.index'))
             ->assertOk()->assertSee('退勤済');
    }

    public function test_退勤時刻が勤怠一覧画面で確認できる(): void
    {
        $this->login();

        // 出勤→退勤の時刻を固定（JST）
        Carbon::setTestNow(Carbon::create(2025, 8, 8, 9, 0, 0));
        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 18, 45, 0));
        $this->post(route('attendance.end'))->assertRedirect(route('attendance.index'));

        $attendance = Attendance::first();
        $this->assertNotNull($attendance->clock_out);

        // 一覧は退勤済のみ表示仕様。年月を明示して取得
        $res = $this->get(route('attendance.list', [
            'year'  => 2025,
            'month' => '08',
        ]))->assertOk();

        // 当日行 + 退勤時刻（ビューは H:i 表示）
        $res->assertSee($attendance->work_date->format('m/d'));
        $res->assertSee($attendance->clock_out->format('H:i')); // 18:45
    }
}

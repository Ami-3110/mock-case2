<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID07 休憩機能
 *
 * テスト内容（シート準拠）：
 * - 休憩入ボタンが正しく機能する
 * - 休憩戻ボタンが正しく機能する
 * - 休憩は一日に何回でもできる
 * - 休憩時刻が勤怠一覧で確認できる（※一覧は退勤済のみ表示の仕様に準拠）
 */
class ID07_BreakTimeTest extends TestCase
{
    use RefreshDatabase;

    private function login(): User
    {
        $u = User::factory()->create();
        $this->actingAs($u);
        return $u;
    }

    public function test_休憩入_休憩戻ができてステータスが遷移する(): void
    {
        $this->login();

        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));

        $this->post(route('break.start'))->assertRedirect(route('attendance.index'));
        $this->assertDatabaseHas('breaks', [
            'attendance_id' => Attendance::first()->id,
            'break_end'     => null,
        ]);
        $this->get(route('attendance.index'))->assertOk()->assertSee('休憩中');

        $this->post(route('break.end'))->assertRedirect(route('attendance.index'));
        $this->assertNotNull(BreakTime::first()->break_end);
        $this->get(route('attendance.index'))->assertOk()->assertSee('出勤中');
    }

    public function test_休憩は複数回可能(): void
    {
        $this->login();

        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));

        $this->post(route('break.start'))->assertRedirect(route('attendance.index'));
        $this->post(route('break.end'))->assertRedirect(route('attendance.index'));

        $this->post(route('break.start'))->assertRedirect(route('attendance.index'));
        $this->post(route('break.end'))->assertRedirect(route('attendance.index'));

        $this->assertSame(2, BreakTime::count());
        $this->assertNull(Attendance::first()->clock_out);
    }

    public function test_休憩時刻が勤怠一覧で確認できる_退勤後に表示される(): void
    {
        $this->login();

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 9, 0, 0));
        $this->post(route('attendance.start'))->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 12, 0, 0));
        $this->post(route('break.start'))->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 12, 30, 0));
        $this->post(route('break.end'))->assertRedirect(route('attendance.index'));

        Carbon::setTestNow(Carbon::create(2025, 8, 8, 18, 0, 0));
        $this->post(route('attendance.end'))->assertRedirect(route('attendance.index'));

        $res = $this->get(route('attendance.list', [
            'year'  => 2025,
            'month' => '08',
        ]))->assertOk();

        $res->assertSee('08/08');

        $html = $res->getContent();
        $this->assertTrue(
            str_contains($html, '00:30') || str_contains($html, '0:30') || str_contains($html, '30分'),
            '勤怠一覧に休憩時間（30分）が表示されていません'
        );
    }
}

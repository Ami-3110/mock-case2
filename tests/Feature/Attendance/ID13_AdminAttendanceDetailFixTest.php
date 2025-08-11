<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID13 管理者：勤怠詳細情報取得・修正機能
 *
 * 要件:
 * - 勤怠詳細画面に表示されるデータが選択したものになっている
 * - 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 備考欄が未入力の場合のエラーメッセージが表示される
 *
 * ルート仕様（既存）:
 * - GET  admin.attendances.showFixForm  /admin/attendances/{id}/fix
 * - POST admin.attendances.fix         /admin/attendances/{id}/fix
 */
class ID13_AdminAttendanceDetailFixTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin(): User
    {
        $admin = User::factory()->create([
            'name'     => '管理者',
            'email'    => 'admin@example.com',
            'is_admin' => true,
        ]);
        $this->actingAs($admin);
        return $admin;
    }

    public function test_勤怠詳細_選択した勤怠が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $u1 = User::factory()->create(['name' => '太郎']);
        $u2 = User::factory()->create(['name' => '花子']);

        $a1 = Attendance::create([
            'user_id'   => $u1->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->copy()->setTime(9, 0),
            'clock_out' => Carbon::today()->copy()->setTime(18, 0),
        ]);
        $a2 = Attendance::create([
            'user_id'   => $u2->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->copy()->setTime(10, 0),
            'clock_out' => Carbon::today()->copy()->setTime(19, 0),
        ]);

        $res = $this->get(route('admin.attendances.showFixForm', $a1->id));
        $res->assertOk()
            ->assertSee('太郎')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertDontSee('花子') 
            ->assertDontSee('10:00')
            ->assertDontSee('19:00');
    }

    public function test_出勤が退勤より後ならエラー(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create();
        $a = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $res = $this->post(route('admin.attendances.fix', $a->id), [
            'fixed_clock_in'  => '19:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [],
            'reason'          => '管理者修正',
        ]);

        $res->assertSessionHasErrors(['fixed_clock_in']);
    }

    public function test_休憩開始が退勤より後ならエラー(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create();
        $a = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $res = $this->post(route('admin.attendances.fix', $a->id), [
            'fixed_clock_in'  => '09:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [
                ['break_start' => '19:00', 'break_end' => '19:10'],
            ],
            'reason'          => '管理者修正',
        ]);

        $res->assertSessionHasErrors(['fixed_breaks.0.break_start']);
    }

    public function test_休憩終了が退勤より後ならエラー(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create();
        $a = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $res = $this->post(route('admin.attendances.fix', $a->id), [
            'fixed_clock_in'  => '09:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [
                ['break_start' => '17:50', 'break_end' => '19:10'],
            ],
            'reason'          => '管理者修正',
        ]);

        $res->assertSessionHasErrors(['fixed_breaks.0.break_end']);
    }

    public function test_備考未入力ならエラー(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create();
        $a = Attendance::create([
            'user_id'   => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $res = $this->post(route('admin.attendances.fix', $a->id), [
            'fixed_clock_in'  => '09:05',
            'fixed_clock_out' => '18:10',
            'fixed_breaks'    => [],
        ]);

        $res->assertSessionHasErrors(['reason']);
    }
}

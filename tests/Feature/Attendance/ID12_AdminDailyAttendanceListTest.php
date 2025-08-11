<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID12 管理者用：勤怠一覧情報取得機能（日別）
 *
 * 要件:
 * - その日になされた全ユーザーの勤怠情報が正確に確認できる
 * - 遷移した際に現在の日付が表示される（パラメータ未指定＝当日）
 * - 「前日」「翌日」を押下すると前後日の勤怠情報が表示される
 */
class ID12_AdminDailyAttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function loginAdmin(): User
    {
        $admin = User::factory()->create([
            'name' => '管理者',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);
        $this->actingAs($admin);
        return $admin;
    }

    public function test_遷移した際に現在の日付が表示される(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $this->get(route('admin.attendance.list'))
            ->assertOk()
            ->assertSee('2025/08/09');
    }

    public function test_日別一覧_当日が表示され_全ユーザーの勤怠が見える(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $u1 = User::factory()->create(['name' => '太郎']);
        $u2 = User::factory()->create(['name' => '花子']);

        Attendance::create([
            'user_id' => $u1->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->copy()->setTime(9, 0),
            'clock_out' => Carbon::today()->copy()->setTime(18, 0),
        ]);
        Attendance::create([
            'user_id' => $u2->id,
            'work_date' => Carbon::today(),
            'clock_in'  => Carbon::today()->copy()->setTime(10, 0),
            'clock_out' => Carbon::today()->copy()->setTime(19, 0),
        ]);

        Attendance::create([
            'user_id' => $u1->id,
            'work_date' => Carbon::yesterday(),
            'clock_in'  => Carbon::yesterday()->copy()->setTime(8, 0),
            'clock_out' => Carbon::yesterday()->copy()->setTime(17, 0),
        ]);
        Attendance::create([
            'user_id' => $u1->id,
            'work_date' => Carbon::tomorrow(),
            'clock_in'  => Carbon::tomorrow()->copy()->setTime(11, 0),
            'clock_out' => Carbon::tomorrow()->copy()->setTime(20, 0),
        ]);

        $res = $this->get(route('admin.attendance.list'));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('2025/08/09', $html);

        $res->assertSee('太郎');
        $res->assertSee('花子');
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('10:00');
        $res->assertSee('19:00');

        $this->assertStringNotContainsString('08:00', $html);
        $this->assertStringNotContainsString('20:00', $html);
    }

    public function test_前日ボタンで前日表示_翌日ボタンで翌日表示(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $u = User::factory()->create(['name' => '太郎']);

        foreach ([-1, 0, 1] as $d) {
            $dDate = Carbon::today()->copy()->addDays($d);
            Attendance::create([
                'user_id'   => $u->id,
                'work_date' => $dDate,
                'clock_in'  => $dDate->copy()->setTime(9 + $d, 0),
                'clock_out' => $dDate->copy()->setTime(18 + $d, 0),
            ]);
        }

        $this->get(route('admin.attendance.list'))
            ->assertOk()
            ->assertSee('2025/08/09')
            ->assertSee('09:00')
            ->assertSee('18:00')
            ->assertSee('前日')
            ->assertSee('翌日');

        $this->get(route('admin.attendance.list', ['date' => '2025-08-08']))
            ->assertOk()
            ->assertSee('2025/08/08')
            ->assertSee('08:00')
            ->assertSee('17:00');

        $this->get(route('admin.attendance.list', ['date' => '2025-08-10']))
            ->assertOk()
            ->assertSee('2025/08/10')
            ->assertSee('10:00')
            ->assertSee('19:00');
    }
}

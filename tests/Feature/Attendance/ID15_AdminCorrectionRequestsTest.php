<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID15 管理者：勤怠情報修正機能
 *
 * 要件:
 * - 承認待ちの修正申請が全て表示されている
 * - 承認済みの修正申請が全て表示されている
 * - 修正申請の詳細内容が正しく表示されている
 * - 修正申請の承認処理が正しく行われる（勤怠・休憩の更新／申請ステータス更新）
 */
class ID15_AdminCorrectionRequestsTest extends TestCase
{
    use RefreshDatabase;

    /** 管理者ログイン */
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

    /** 承認待ち/承認済みの申請が一覧に表示されること */
    public function test_申請一覧_承認待ちと承認済みが全て表示される(): void
    {
        $this->loginAdmin();
        $u1 = User::factory()->create(['name' => '太郎']);
        $u2 = User::factory()->create(['name' => '花子']);

        $a1 = Attendance::create([
            'user_id' => $u1->id,
            'work_date' => Carbon::create(2025,8,8),
            'clock_in'  => Carbon::create(2025,8,8,9,0),
            'clock_out' => Carbon::create(2025,8,8,18,0),
        ]);
        
        $a2 = Attendance::create([
            'user_id' => $u2->id,
            'work_date' => Carbon::create(2025,8,9),
            'clock_in'  => Carbon::create(2025,8,9,10,0),
            'clock_out' => Carbon::create(2025,8,9,19,0),
        ]);

        // 承認待ち 2件
        AttendanceCorrectRequest::create([
            'attendance_id' => $a1->id, 'user_id' => $u1->id,
            'status' => 'pending', 'reason' => '昼休み延長',
            'fixed_clock_in' => '09:05', 'fixed_clock_out' => '18:10',
        ]);
        AttendanceCorrectRequest::create([
            'attendance_id' => $a2->id, 'user_id' => $u2->id,
            'status' => 'pending', 'reason' => '電車遅延',
            'fixed_clock_in' => '10:05', 'fixed_clock_out' => '19:00',
        ]);

        // 承認済み 1件
        AttendanceCorrectRequest::create([
            'attendance_id' => $a1->id, 'user_id' => $u1->id,
            'status' => 'approved', 'reason' => '過去分修正',
            'fixed_clock_in' => '09:00', 'fixed_clock_out' => '18:00',
        ]);

        $res = $this->get(route('stamp_correction_request.list'));
        $res->assertOk();
        $html = $res->getContent();

        // 見出し（ゆるめに）
        $this->assertTrue(
            str_contains($html, '承認待ち') || str_contains($html, 'Pending'),
            '承認待ちセクションが見つかりません'
        );
        $this->assertTrue(
            str_contains($html, '承認済み') || str_contains($html, 'Approved'),
            '承認済みセクションが見つかりません'
        );

        // 各申請の理由（= レコードの存在）で確認
        $this->assertStringContainsString('昼休み延長', $html);
        $this->assertStringContainsString('電車遅延', $html);
        $this->assertStringContainsString('過去分修正', $html);
    }

    /** 承認画面（詳細）で内容が正しく表示されること */
    public function test_申請詳細_内容が正しく表示される(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create(['name' => '田中']);
        $a = Attendance::create([
            'user_id' => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $req = AttendanceCorrectRequest::create([
            'attendance_id' => $a->id,
            'user_id'       => $u->id,
            'status'        => 'pending',
            'reason'        => '管理者確認テスト',
            'fixed_clock_in'=> '09:10',
            'fixed_clock_out'=> '18:20',
            'fixed_breaks'  => [
                ['break_start'=>'12:00','break_end'=>'12:20'],
                ['break_start'=>'15:00','break_end'=>'15:10'],
            ],
        ]);

        $this->get(route('admin.correction.approve', $req->id))
            ->assertOk()
            ->assertSee('管理者確認テスト')
            ->assertSee('09:10')
            ->assertSee('18:20')
            ->assertSee('12:00')
            ->assertSee('12:20')
            ->assertSee('15:00')
            ->assertSee('15:10');
    }

    /** 承認処理：勤怠/休憩が更新され、申請が承認済みになること */
    public function test_承認処理で勤怠更新と申請承認が行われる(): void
    {
        $this->loginAdmin();

        $u = User::factory()->create();
        $date = Carbon::create(2025, 8, 8);

        $a = Attendance::create([
            'user_id' => $u->id,
            'work_date' => $date,
            'clock_in'  => $date->copy()->setTime(9, 0),
            'clock_out' => $date->copy()->setTime(18, 0),
        ]);

        // 既存の休憩（後で差し替えられるはず）
        BreakTime::create([
            'attendance_id' => $a->id,
            'break_start' => $date->copy()->setTime(12, 0),
            'break_end'   => $date->copy()->setTime(12, 30),
        ]);

        // 修正申請（承認待ち）
        $req = AttendanceCorrectRequest::create([
            'attendance_id' => $a->id,
            'user_id'       => $u->id,
            'status'        => 'pending',
            'reason'        => '承認処理テスト',
            'fixed_clock_in'=> '09:05',
            'fixed_clock_out'=> '18:10',
            'fixed_breaks'  => [
                ['break_start'=>'12:05','break_end'=>'12:25'],
                ['break_start'=>'15:00','break_end'=>'15:05'],
            ],
        ]);

        // 承認実行
        $res = $this->post(route('admin.correction.approve.submit', $req->id));
        $res->assertSuccessful();

        // 申請が承認済み
        $this->assertDatabaseHas('attendance_correct_requests', [
            'id'     => $req->id,
            'status' => 'approved',
        ]);

        // 勤怠が更新されている
        $a->refresh();
        $this->assertEquals('09:05', $a->clock_in->format('H:i'));
        $this->assertEquals('18:10', $a->clock_out->format('H:i'));

        // 休憩が置き換わっている（件数&内容で確認）
        $breaks = BreakTime::where('attendance_id', $a->id)->orderBy('break_start')->get();
        $this->assertCount(2, $breaks);
        $this->assertEquals('12:05', Carbon::parse($breaks[0]->break_start)->format('H:i'));
        $this->assertEquals('12:25', Carbon::parse($breaks[0]->break_end)->format('H:i'));
        $this->assertEquals('15:00', Carbon::parse($breaks[1]->break_start)->format('H:i'));
        $this->assertEquals('15:05', Carbon::parse($breaks[1]->break_end)->format('H:i'));
    }
}

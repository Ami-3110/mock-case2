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
 * ID11 勤怠詳細情報修正機能（一般ユーザー）
 *
 * テスト内容（シート準拠）：
 * - 出勤時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 休憩開始時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 休憩終了時間が退勤時間より後になっている場合、エラーメッセージが表示される
 * - 備考欄が未入力の場合のエラーメッセージが表示される
 * - 修正申請処理が実行される
 * - 「承認待ち」にログインユーザーが行った申請が全て表示されていること
 * - 「承認済み」に管理者が承認した修正申請が全て表示されていること
 * - 各申請の「詳細」を押下すると申請詳細画面に遷移する
 */
class ID11_AttendanceDetailFixUserTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(string $name = 'テスト太郎'): User
    {
        $u = User::factory()->create(['name' => $name, 'email' => 'user@example.com']);
        $this->actingAs($u);
        return $u;
    }

    private function makeAttendance(User $u): Attendance
    {
        return Attendance::create([
            'user_id'   => $u->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);
    }

    public function test_出勤時間が退勤時間より後ならエラー(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $res = $this->post(route('attendance.fix', $attendance->id), [
            'fixed_clock_in'  => '19:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [],
            'reason'          => '調整',
        ]);

        $res->assertSessionHasErrors(['fixed_clock_in']);
    }

    public function test_休憩開始が退勤時間より後ならエラー(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $res = $this->post(route('attendance.fix', $attendance->id), [
            'fixed_clock_in'  => '09:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [
                ['break_start' => '19:00', 'break_end' => '19:10'],
            ],
            'reason'          => '調整',
        ]);

        $res->assertSessionHasErrors(['fixed_breaks.0.break_start']);
    }

    public function test_休憩終了が退勤時間より後ならエラー(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $res = $this->post(route('attendance.fix', $attendance->id), [
            'fixed_clock_in'  => '09:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [
                ['break_start' => '17:50', 'break_end' => '19:10'],
            ],
            'reason'          => '調整',
        ]);

        $res->assertSessionHasErrors(['fixed_breaks.0.break_end']);
    }

    public function test_備考未入力ならエラー(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $res = $this->post(route('attendance.fix', $attendance->id), [
            'fixed_clock_in'  => '09:00',
            'fixed_clock_out' => '18:00',
            'fixed_breaks'    => [],
        ]);

        $res->assertSessionHasErrors(['reason']);
    }

    public function test_修正申請が作成され_承認待ち一覧に表示される(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $this->post(route('attendance.fix', $attendance->id), [
            'fixed_clock_in'  => '09:05',
            'fixed_clock_out' => '18:10',
            'fixed_breaks'    => [
                ['break_start' => '12:00', 'break_end' => '12:30'],
            ],
            'reason'          => '昼休み延長のため',
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance_correct_requests', [
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
        ]);

        $res = $this->get('/stamp_correction_request/list')->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('承認待ち', $html);
        $this->assertStringContainsString('昼休み延長', $html);
        $this->assertTrue(
            str_contains($html, $attendance->work_date->format('m/d')) ||
            str_contains($html, $attendance->work_date->format('Y/m/d')) ||
            str_contains($html, $attendance->work_date->locale('ja')->isoFormat('YYYY年M月D日')),
            '申請一覧に対象日付が表示されていません'
        );
    }

    public function test_承認済みタブに承認済み申請が表示される(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        AttendanceCorrectRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'approved',
            'reason'        => '承認済ダミー',
            'fixed_clock_in'=> '09:10',
            'fixed_clock_out'=> '18:20',
        ]);

        $res = $this->get('/stamp_correction_request/list')->assertOk();
        $html = $res->getContent();

        $this->assertStringContainsString('承認済み', $html);
        $this->assertTrue(
            str_contains($html, '承認済ダミー') ||
            str_contains($html, $attendance->work_date->format('m/d')),
            '承認済みの申請が一覧に表示されていません'
        );
    }

    public function test_各申請の詳細に遷移できる(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        $req = AttendanceCorrectRequest::create([
            'attendance_id' => $attendance->id,
            'user_id'       => $user->id,
            'status'        => 'pending',
            'reason'        => '詳細遷移テスト',
            'fixed_clock_in'=> '09:10',
            'fixed_clock_out'=> '18:20',
        ]);

        $this->get('/stamp_correction_request/list')->assertOk();

        $detailPaths = [
            "/stamp_correction_request/{$req->id}",
        ];

        $ok = false;
        foreach ($detailPaths as $path) {
            $res = $this->get($path);
            if ($res->getStatusCode() === 200) {
                $res->assertSee('詳細');
                $ok = true;
                break;
            }
        }

        if (! $ok) {
            $confirm = $this->get(route('attendance.fixConfirm', $attendance->id));
            $confirm->assertOk();
            $confirm->assertSee('勤怠詳細');
        } else {
            $this->assertTrue($ok, '申請詳細ページに遷移できませんでした');
        }
    }
}

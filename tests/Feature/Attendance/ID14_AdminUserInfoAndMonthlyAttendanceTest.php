<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID14 管理者：ユーザー情報取得機能（スタッフ一覧＋月次勤怠）
 *
 * 要件（整理）:
 * 1. 管理者が全一般ユーザーの「氏名」「メールアドレス」を確認できる（スタッフ一覧）
 * 2. ユーザーの勤怠情報が正しく表示される（ユーザー別・月次勤怠）
 * 3. 「前月」押下で前月の情報が表示される
 * 4. 「翌月」押下で翌月の情報が表示される
 * 5. 「詳細」を押下すると、その日の勤怠詳細（管理者修正画面）に遷移する
 */
class ID14_AdminUserInfoAndMonthlyAttendanceTest extends TestCase
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

    /** スタッフ一覧：全一般ユーザーの氏名とメールが見える（管理者自身は除外でも可） */
    public function test_スタッフ一覧で一般ユーザーの氏名メールが見える(): void
    {
        $this->loginAdmin();

        $u1 = User::factory()->create(['name' => '田中 太郎', 'email' => 'taro@example.com', 'is_admin' => false]);
        $u2 = User::factory()->create(['name' => '山田 花子', 'email' => 'hanako@example.com', 'is_admin' => false]);

        $res = $this->get(route('admin.staff.list'));
        $res->assertOk()
            ->assertSee('スタッフ一覧');
        $html = $res->getContent();

        $this->assertStringContainsString('田中 太郎', $html);
        $this->assertStringContainsString('taro@example.com', $html);
        $this->assertStringContainsString('山田 花子', $html);
        $this->assertStringContainsString('hanako@example.com', $html);
    }

    public function test_ユーザー月次勤怠_当月が表示され_日別勤怠が見える(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $u = User::factory()->create(['name' => '田中 太郎']);

        $a1 = Attendance::create([
            'user_id' => $u->id,
            'work_date' => Carbon::create(2025, 8, 1),
            'clock_in'  => Carbon::create(2025, 8, 1, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 1, 18, 0),
        ]);
        $a2 = Attendance::create([
            'user_id' => $u->id,
            'work_date' => Carbon::create(2025, 8, 2),
            'clock_in'  => Carbon::create(2025, 8, 2, 10, 0),
            'clock_out' => Carbon::create(2025, 8, 2, 19, 0),
        ]);

        Attendance::create([
            'user_id' => $u->id,
            'work_date' => Carbon::create(2025, 7, 31),
            'clock_in'  => Carbon::create(2025, 7, 31, 9, 0),
            'clock_out' => Carbon::create(2025, 7, 31, 18, 0),
        ]);
        Attendance::create([
            'user_id' => $u->id,
            'work_date' => Carbon::create(2025, 9, 1),
            'clock_in'  => Carbon::create(2025, 9, 1, 9, 0),
            'clock_out' => Carbon::create(2025, 9, 1, 18, 0),
        ]);

        $res = $this->get(route('admin.attendance.staff', [
            'id' => $u->id,
            'year' => 2025,
            'month' => 8,
        ]));
        $res->assertOk();
        $html = $res->getContent();

        $this->assertTrue(
            str_contains($html, '2025/08') ||
            str_contains($html, '2025年8月') ||
            str_contains($html, '2025-08'),
            '当月の見出しが表示されていません'
        );

        $res->assertSee('08/01');
        $res->assertSee('08/02');
        $res->assertSee('09:00');
        $res->assertSee('18:00');
        $res->assertSee('10:00');
        $res->assertSee('19:00');

        $this->assertStringNotContainsString('07/31', $html);
        $this->assertStringNotContainsString('09/01', $html);
    }

    public function test_ユーザー月次勤怠_前月ボタン翌月ボタンで月を切り替えられる(): void
    {
        Carbon::setTestNow(Carbon::create(2025, 8, 9, 9, 0));
        $this->loginAdmin();

        $u = User::factory()->create(['name' => '田中 太郎']);

        foreach ([['y'=>2025,'m'=>7,'h'=>8], ['y'=>2025,'m'=>8,'h'=>9], ['y'=>2025,'m'=>9,'h'=>10]] as $p) {
            $d = Carbon::create($p['y'], $p['m'], 15);
            Attendance::create([
                'user_id' => $u->id,
                'work_date' => $d,
                'clock_in'  => $d->copy()->setTime($p['h'], 0),
                'clock_out' => $d->copy()->setTime($p['h'] + 9, 0),
            ]);
        }

        $this->get(route('admin.attendance.staff', ['id'=>$u->id,'year'=>2025,'month'=>8]))
            ->assertOk()->assertSee('2025')->assertSee('08');

        $this->get(route('admin.attendance.staff', ['id'=>$u->id,'year'=>2025,'month'=>7]))
            ->assertOk()->assertSee('2025')->assertSee('07')->assertSee('08:00');

        $this->get(route('admin.attendance.staff', ['id'=>$u->id,'year'=>2025,'month'=>9]))
            ->assertOk()->assertSee('2025')->assertSee('09')->assertSee('10:00');
    }

    public function test_ユーザー月次勤怠_詳細ボタンでその日の勤怠詳細に遷移する(): void
    {
        $this->loginAdmin();
        $u = User::factory()->create();
        $d = Carbon::create(2025, 8, 2);

        $a = Attendance::create([
            'user_id' => $u->id,
            'work_date' => $d,
            'clock_in'  => $d->copy()->setTime(10, 0),
            'clock_out' => $d->copy()->setTime(19, 0),
        ]);

        $this->get(route('admin.attendance.staff', ['id'=>$u->id,'year'=>2025,'month'=>8]))
            ->assertOk();

        $this->get(route('admin.attendances.showFixForm', $a->id))
            ->assertOk()
            ->assertSee('10:00')
            ->assertSee('19:00');
    }
}

<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID09 勤怠一覧画面
 *
 * テスト内容（シート準拠）：
 * 1. 自分が行った勤怠情報がすべて表示される
 * 2. 勤怠一覧画面に遷移した際に現在の月が表示される
 * 3. 「前月」を押下した時に表示月の前月の情報が表示される
 * 4. 「翌月」を押下した時に表示月の翌月の情報が表示される
 * 5. 「詳細」を押下すると、その日の勤怠詳細画面に遷移する
 */
class ID09_AttendanceListTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    public function test_自分の勤怠が一覧に表示される_現在月表示も確認(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $res = $this->get(route('attendance.list', ['year' => 2025, 'month' => '08']))
            ->assertOk();

        $res->assertSee('2025/08');

        $res->assertSee('08/08')
            ->assertSee('09:00')
            ->assertSee('18:00');
    }

    public function test_他ユーザーの勤怠は表示されない(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        Attendance::create([
            'user_id'   => User::factory()->create()->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 30),
            'clock_out' => Carbon::create(2025, 8, 8, 17, 0),
        ]);

        $res = $this->get(route('attendance.list', ['year' => 2025, 'month' => '08']))
            ->assertOk();

        $html = $res->getContent();

        $this->assertSame(1, substr_count($html, '08/08'));

        $this->assertStringNotContainsString('09:30', $html);
        $this->assertStringNotContainsString('17:00', $html);
    }

    public function test_前月ボタン相当_前月の情報が表示される(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 7, 15),
            'clock_in'  => Carbon::create(2025, 7, 15, 9, 0),
            'clock_out' => Carbon::create(2025, 7, 15, 18, 0),
        ]);

        $res = $this->get(route('attendance.list', ['year' => 2025, 'month' => '07']))
            ->assertOk();

        $res->assertSee('2025/07')
            ->assertSee('07/15');
    }

    public function test_翌月ボタン相当_翌月の情報が表示される(): void
    {
        $user = $this->loginUser();

        Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 9, 5),
            'clock_in'  => Carbon::create(2025, 9, 5, 9, 0),
            'clock_out' => Carbon::create(2025, 9, 5, 18, 0),
        ]);

        $res = $this->get(route('attendance.list', ['year' => 2025, 'month' => '09']))
            ->assertOk();

        $res->assertSee('2025/09')
            ->assertSee('09/05');
    }

    public function test_詳細ボタンで勤怠詳細画面に遷移できる(): void
    {
        $user = $this->loginUser();

        $attendance = Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 0),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 0),
        ]);

        $this->get(route('attendance.list', ['year' => 2025, 'month' => '08']))->assertOk();

        $res = $this->get(route('attendance.fixForm', $attendance->id))->assertOk();

        $res->assertSee('勤怠詳細');

        $res->assertSee($attendance->work_date->format('Y') . '年');
        $res->assertSee($attendance->work_date->format('n月j日'));

        $html = $res->getContent();
        $this->assertMatchesRegularExpression('/name="fixed_clock_in"[^>]*value="?\s*09:00\s*"?/u', $html);
        $this->assertMatchesRegularExpression('/name="fixed_clock_out"[^>]*value="?\s*18:00\s*"?/u', $html);
    }
}


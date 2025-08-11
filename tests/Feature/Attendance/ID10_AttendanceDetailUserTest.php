<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

/**
 * ID10 勤怠詳細情報取得機能（一般ユーザー）
 *
 * テスト内容（シート準拠）：
 * - 勤怠詳細画面の「名前」がログインユーザーの氏名になっている
 * - 勤怠詳細画面の「日付」が選択した日付になっている
 * - 「出勤・退勤」にて記されている時間がログインユーザーの打刻と一致している
 * - 「休憩」にて記されている時間がログインユーザーの打刻と一致している
 */
class ID10_AttendanceDetailUserTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(string $name = 'あみ'): User
    {
        $user = User::factory()->create(['name' => $name]);
        $this->actingAs($user);
        return $user;
    }

    private function makeAttendance(User $user, array $override = []): Attendance
    {
        $base = [
            'user_id'   => $user->id,
            'work_date' => Carbon::create(2025, 8, 8),
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 15),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 45),
        ];
        return Attendance::create(array_merge($base, $override));
    }

    public function test_名前がログインユーザーの氏名になっている(): void
    {
        $user = $this->loginUser('斉藤 愛美');
        $attendance = $this->makeAttendance($user);

        $this->get(route('attendance.fixForm', $attendance->id))
            ->assertOk()
            ->assertSee('勤怠詳細')
            ->assertSee('斉藤 愛美'); // <td>{{ $attendance->user->name }}</td>
    }

    public function test_日付が選択した日付になっている(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user, [
            'work_date' => Carbon::create(2025, 8, 8),
        ]);

        $res = $this->get(route('attendance.fixForm', $attendance->id))->assertOk();

        // 分割表示：Y年 と n月j日
        $res->assertSee($attendance->work_date->format('Y') . '年');
        $res->assertSee($attendance->work_date->format('n月j日')); // 0埋めなし
    }

    public function test_出勤退勤の時間が打刻と一致している(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user, [
            'clock_in'  => Carbon::create(2025, 8, 8, 9, 15),
            'clock_out' => Carbon::create(2025, 8, 8, 18, 45),
        ]);

        $res  = $this->get(route('attendance.fixForm', $attendance->id))->assertOk();
        $html = $res->getContent();

        // <input name="fixed_clock_in" value="09:15"> / <input name="fixed_clock_out" value="18:45">
        $this->assertMatchesRegularExpression('/name="fixed_clock_in"[^>]*value="?\s*09:15\s*"?/u', $html);
        $this->assertMatchesRegularExpression('/name="fixed_clock_out"[^>]*value="?\s*18:45\s*"?/u', $html);
    }

    public function test_休憩の時間が打刻と一致している(): void
    {
        $user = $this->loginUser();
        $attendance = $this->makeAttendance($user);

        // 休憩2本（index=0,1 でフォームに並ぶ想定）
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => Carbon::create(2025, 8, 8, 12, 0),
            'break_end'     => Carbon::create(2025, 8, 8, 12, 30),
        ]);
        BreakTime::create([
            'attendance_id' => $attendance->id,
            'break_start'   => Carbon::create(2025, 8, 8, 15, 10),
            'break_end'     => Carbon::create(2025, 8, 8, 15, 20),
        ]);

        $res  = $this->get(route('attendance.fixForm', $attendance->id))->assertOk();
        $html = $res->getContent();

        // input name は fixed_breaks[0][break_start] / fixed_breaks[0][break_end] ... に一致
        $this->assertMatchesRegularExpression('/name="fixed_breaks\[0\]\[break_start\]"[^>]*value="?\s*12:00\s*"?/u', $html);
        $this->assertMatchesRegularExpression('/name="fixed_breaks\[0\]\[break_end\]"[^>]*value="?\s*12:30\s*"?/u', $html);

        $this->assertMatchesRegularExpression('/name="fixed_breaks\[1\]\[break_start\]"[^>]*value="?\s*15:10\s*"?/u', $html);
        $this->assertMatchesRegularExpression('/name="fixed_breaks\[1\]\[break_end\]"[^>]*value="?\s*15:20\s*"?/u', $html);
    }
}

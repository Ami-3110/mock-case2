<?php

namespace Tests\Feature\Attendance;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * ID04 現在日時の表示
 *
 * テスト内容（シート準拠）：
 * - 現在の日時情報がUIと同じ形式で出力されている
 */
class ID04_CurrentDateTimeTest extends TestCase
{
    use RefreshDatabase;

    private function loginUser(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        return $user;
    }

    public function test_ステータス画面で現在の日時がUI形式で表示される(): void
    {
        $this->loginUser();

        \Carbon\Carbon::setTestNow(\Carbon\Carbon::create(2025, 8, 8, 9, 42, 0));
        $date = now()->locale('ja')->isoFormat('YYYY年M月D日（dd）');
        $time = now()->format('H:i');

        $res = $this->get(route('attendance.index'));

        $res->assertOk()
            ->assertSee($date)
            ->assertSee($time);
    }
}

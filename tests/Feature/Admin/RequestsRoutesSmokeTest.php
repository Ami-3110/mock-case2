<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Attendance;
use App\Models\AttendanceCorrectRequest;
use Carbon\Carbon;

/**
 * ID01 会員登録機能（一般ユーザー）
 *
 *本テストは仕様変更（コーチ指示による）への対応についてのテストです。
 *今後の変更に備え残しておきますが、採点基準とは無関係です。
 */
class RequestsRoutesSmokeTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);
    }

    private function makeStaff(): User
    {
        return User::factory()->create([
            'is_admin' => false,
            'email_verified_at' => now(),
        ]);
    }

    private function makeAttendanceFor(User $user): Attendance
    {
        return Attendance::create([
            'user_id'   => $user->id,
            'work_date' => Carbon::today()->toDateString(),
            'clock_in'  => Carbon::today()->setTime(9,0),
            'clock_out' => Carbon::today()->setTime(18,0),
        ]);
    }

    private function makeAcr(Attendance $attendance): AttendanceCorrectRequest
    {
        return AttendanceCorrectRequest::create([
            'user_id'         => $attendance->user_id,
            'attendance_id'   => $attendance->id,
            'reason'          => 'テスト用',
            'status'          => 'pending',
            'fixed_clock_in'  => Carbon::today()->setTime(9,0),
            'fixed_clock_out' => Carbon::today()->setTime(18,0),
            'fixed_breaks'    => json_encode([['break_start'=>'12:00','break_end'=>'12:45']]),
        ]);
    }

    public function test_admin_can_open_requests_index(): void
    {
        $admin = $this->makeAdmin();

        $this->actingAs($admin)
            ->get(route('admin.requests.index'))
            ->assertStatus(200);
    }

    public function test_admin_can_open_request_show(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $attendance = $this->makeAttendanceFor($staff);
        $acr = $this->makeAcr($attendance);

        $this->actingAs($admin)
            ->get(route('admin.requests.show', $acr->id))
            ->assertStatus(200);
    }

    public function test_admin_can_post_request_update(): void
    {
        $admin = $this->makeAdmin();
        $staff = $this->makeStaff();
        $attendance = $this->makeAttendanceFor($staff);
        $acr = $this->makeAcr($attendance);

        $this->actingAs($admin)
            ->post(route('admin.requests.update', $acr->id), [])
            ->assertStatus(200); 
    }
}

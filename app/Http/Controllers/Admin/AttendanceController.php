<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;



class AttendanceController extends Controller
{
    // 本日の勤怠一覧 //
    public function list($date = null)
    {
        $targetDate = $date ? Carbon::parse($date) : Carbon::today();
        $prevDate = $targetDate->copy()->subDay();
        $nextDate = $targetDate->copy()->addDay();

        $attendances = Attendance::with(['user', 'breakTimes'])
            ->whereDate('work_date', $targetDate->toDateString())
            ->get();


        return view('admin.attendance.list', [
            'attendances' => $attendances,
            'date' => $targetDate,
            'prevDate' => $prevDate,
            'nextDate' => $nextDate,
        ]);
    }

    // スタッフ一覧表示　//
    public function staffList()
    {
        $staff = User::where('is_admin', false)->get();

        return view('admin.staff.index', compact('staff'));
    }

    // 各スタッフの月次勤怠一覧表示 //
    public function staffAttendances(User $user)
    {
        $attendances = $user->attendances()
            ->orderBy('work_date', 'desc')
            ->get();

        return view('admin.staff.attendances', compact('user', 'attendances'));
    }

    // 承認申請一覧 //
    public function applicationIndex()
    {
        $pendingApplications = AttendanceCorrectRequest::with('user', 'attendance')
            ->where('status', 'pending')   // ステータス名が 'pending' なら変更してね
            ->orderByDesc('created_at')
            ->get();

        $approvedApplications = AttendanceCorrectRequest::with('user', 'attendance')
            ->where('status', 'approved')   // '承認済み' なら変更
            ->orderByDesc('created_at')
            ->get();

        return view('stamp_correction_request.list', [
            'pendingApplications' => $pendingApplications,
            'approvedApplications' => $approvedApplications,
        ]);
    }

    // 修正申請の承認画面表示 //
    public function approveForm(AttendanceCorrectRequest $attendanceCorrectRequest)
    {
        $user = $attendanceCorrectRequest->user;
        return view('stamp_correction_request.approve', [
            'attendanceCorrectRequest' => $attendanceCorrectRequest,
            'user' => $attendanceCorrectRequest->user,
        ]);
    }

    // 修正申請の承認処理 //
    public function approveApplication(AttendanceCorrectRequest $attendanceCorrectRequest)
    {
        $attendance = $attendanceCorrectRequest->attendance;

        DB::transaction(function () use ($attendanceCorrectRequest, $attendance) {
            // 勤怠情報を修正申請内容で更新
            $attendance->update([
                'clock_in' => $attendanceCorrectRequest->fixed_clock_in,
                'clock_out' => $attendanceCorrectRequest->fixed_clock_out,
            ]);

            // 休憩時間がある場合、更新または作成
            if ($attendanceCorrectRequest->fixed_break_start && $attendanceCorrectRequest->fixed_break_end) {
                // 既存のbreakTimesが複数なら要ループで対応
                // ここでは仮に最初のbreakTimeを更新と想定
                $break = $attendance->breakTimes()->first();
                if ($break) {
                    $break->update([
                        'break_start' => $attendanceCorrectRequest->fixed_break_start,
                        'break_end' => $attendanceCorrectRequest->fixed_break_end,
                    ]);
                }
            }

            // 修正申請を承認済みに更新
            $attendanceCorrectRequest->update([
                'status' => 'approved',
            ]);
        });

        // トランザクション後に更新済みデータを再取得（リフレッシュ）
        $attendanceCorrectRequest->refresh();

        return view('stamp_correction_request.approve', [
            'attendanceCorrectRequest' => $attendanceCorrectRequest,
            'user' => $attendanceCorrectRequest->user,
        ]);
    }

    

}

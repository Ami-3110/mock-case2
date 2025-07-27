<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Carbon;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use Illuminate\Support\Facades\Log;


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
        $pendingApplications = Application::with('user', 'attendance')
            ->where('status', '承認待ち')
            ->orderByDesc('created_at')
            ->get();

        $approvedApplications = Application::with('user', 'attendance')
            ->where('status', '承認済み')
            ->orderByDesc('created_at')
            ->get();

        return view('stamp_correction_request.list', [
            'pendingApplications' => $pendingApplications,
            'approvedApplications' => $approvedApplications,
        ]);
    }

    // 管理者による承認処理 //
    public function approveApplication($id)
    {
        // is_approved = true, approved_by, approved_at を更新
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Session;

class AttendanceController extends Controller
{
    use AuthorizesRequests;
    /* 勤怠登録画面の表示 */
    public function index()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', now()->toDateString())
            ->latest()
            ->first();

        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->clock_out) {
            $status = '退勤済';
        } elseif ($attendance->clock_in) {
            $break = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest()
                ->first();

            $status = $break ? '休憩中' : '勤務中';
        } else {
            $status = '勤務外';
        }

        return view('attendance.index', [
            'status' => $status,
            'date' => now()->format('Y年n月j日（D）'),
            'time' => now()->format('H:i:s'),
        ]);
    }

    /* 出勤打刻 */
    public function clockIn(Request $request)
    {
        $user = Auth::user();

        $attendance = new Attendance();
        $attendance->user_id = $user->id;
        $attendance->work_date = now()->toDateString();
        $attendance->clock_in = now();
        $attendance->save();

        Session::put('attendance_id', $attendance->id);

        return redirect()->route('attendance.index');
    }

    /* 休憩開始 */
    public function breakStart(Request $request)
    {
        $attendanceId = Session::get('attendance_id');
        if (!$attendanceId) {
            return redirect()->route('attendance.index');
        }

        $break = new BreakTime();
        $break->attendance_id = $attendanceId;
        $break->break_start = now();
        $break->save();

        Session::put('break_time_id', $break->id);

        return redirect()->route('attendance.index');
    }

    /* 休憩終了 */
    public function breakEnd(Request $request)
    {
        $breakTimeId = Session::get('break_time_id');
        if (!$breakTimeId) {
            return redirect()->route('attendance.index');
        }

        $break = BreakTime::find($breakTimeId);
        if (!$break) {
            return redirect()->route('attendance.index');
        }

        $break->break_end = now();
        $break->save();

        Session::forget('break_time_id'); 

        return redirect()->route('attendance.index');
    }

    /* 退勤打刻 */
    public function clockOut()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', now()->toDateString())
            ->latest()  // フレックス制導入・シフト勤務等での使用可能性を考慮してつけてありますが、現状の仕様では不要かもしれません。勤怠管理方法によります。
            ->first();

        if (!$attendance) {
            return redirect()->back();
        }
        if ($attendance->clock_out) {
            return redirect()->back();
        }

        $attendance->clock_out = now();
        $attendance->save();

        return redirect()->route('attendance.index');
    }

    /* 当月勤怠一覧表示 */
    public function list(Request $request)
    {
        $user = Auth::user();

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $startDate = \Carbon\Carbon::create($year, $month, 1)->startOfDay();
        $endDate = (clone $startDate)->endOfMonth()->endOfDay();

        $attendances = Attendance::with(['breakTimes', 'user'])
            ->where('user_id', $user->id)
            ->whereBetween('work_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderBy('work_date', 'asc')
            ->get();

        $prevMonth = (clone $startDate)->subMonth();
        $nextMonth = (clone $startDate)->addMonth();

        return view('attendance.list', compact(
            'attendances', 'year', 'month', 'prevMonth', 'nextMonth'
        ));
    }

    /* 勤怠修正申請フォームの表示 */
    public function show($id)
    {
        $user = Auth::user();
    
        $attendance = Attendance::with('breakTimes', 'user', 'application')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $application = $attendance->application;
    
        $isEditable = !$application || $application->status !== '承認待ち';
    
        return view('attendance.show', [
            'attendance' => $attendance,
            'breaks' => $attendance->breakTimes,
            'isEditable' => $isEditable,
        ]);
    } 

    /* 勤怠修正申請の送信 */
    public function requestFix(Request $request, $id)
    {
        $attendance = Attendance::with('breakTimes', 'user', 'application')->findOrFail($id);
    
        $application = $attendance->application ?? new Application();
        $application->user_id = Auth::id();
        $application->attendance_id = $attendance->id;
        $application->reason = $request->input('reason');
        $application->status = '承認待ち';
        $application->fixed_clock_in = $request->input('fixed_clock_in');
        $application->fixed_clock_out = $request->input('fixed_clock_out');
        $application->fixed_break_start = $request->input('fixed_break_start');
        $application->fixed_break_end = $request->input('fixed_break_end');
        $application->fixed_breaks = json_encode($request->input('breaks', []));
        $application->save();
    
        $isEditable = false;
    
        return view('attendance.show', [
            'attendance' => $attendance,
            'breaks' => $attendance->breakTimes,
            'isEditable' => $isEditable,
        ]);
    }
    
    /* 勤怠修正申請一覧 */
    public function correctionList()
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



}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;
use App\Http\Requests\UserStampCorrectionRequest;

class AttendanceController extends Controller
{
    /* 勤怠登録画面の表示 */
    public function index()
    {
        $user = Auth::user();
        $attendance = Attendance::todayByUser($user->id)
            ->with('breakTimes') // 休憩中判定に使う
            ->first();

        if ($attendance && $attendance->clock_out) {
            $status = '退勤済';
        } elseif ($attendance && $attendance->breakTimes->firstWhere('break_end', null)) {
            $status = '休憩中';
        } elseif ($attendance && $attendance->clock_in && !$attendance->clock_out) {
            $status = '出勤中';
        } else {
            $status = '勤務外';
        }

        return view('attendance.index', [
            'status' => $status,
            'date' => now()->locale('ja')->isoFormat('YYYY年M月D日（dd）'),
            'time' => now()->format('H:i'),
        ]);
    }

    /* 出勤打刻 */
    public function clockIn(Request $request)
    {
        if (Attendance::where('user_id', Auth::id())
            ->whereDate('work_date', now()->toDateString())
            ->exists()) {
            return redirect()->route('attendance.index');
        }

        $attendance = Attendance::create([
            'user_id' => Auth::id(),
            'work_date' => now()->toDateString(),
            'clock_in' => now(),
        ]);

        Session::put('attendance_id', $attendance->id);

        return redirect()->route('attendance.index');
    }

    /* 休憩開始 */
    public function breakStart()
    {
        if (!$attendanceId = Session::get('attendance_id')) {
            return redirect()->route('attendance.index');
        }

        $break = BreakTime::create([
            'attendance_id' => $attendanceId,
            'break_start' => now(),
        ]);

        Session::put('break_time_id', $break->id);     
        return redirect()->route('attendance.index');
    }

    /* 休憩終了 */
    public function breakEnd()
    {
        if (!$breakTimeId = Session::get('break_time_id')) {
            return redirect()->route('attendance.index');
        }
    
        $break = BreakTime::find($breakTimeId);
        if (!$break) {
            return redirect()->route('attendance.index');
        }
    
        $break->update(['break_end' => now()]);
        Session::forget('break_time_id');
        return redirect()->route('attendance.index');
    }

    /* 退勤打刻 */
    public function clockOut()
    {
        $attendance = Attendance::todayByUser(Auth::id())->first();
        if (!$attendance || $attendance->clock_out) {
            return redirect()->back();
        }

        $attendance->update(['clock_out' => now()]);
        return redirect()->route('attendance.index');
    }

    /* 当月勤怠一覧表示 */
    public function list(Request $request)
    {
        $user = Auth::user();
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendances = Attendance::with(['breakTimes', 'user', 'latestAttendanceCorrectRequest'])
        ->where('user_id', $user->id)
        ->whereNotNull('clock_out')
        ->whereBetween('work_date', [$start->toDateString(), $end->toDateString()])
        ->orderBy('work_date')
        ->get();    

        return view('attendance.list', [
            'attendances' => $attendances,
            'year' => $year,
            'month' => $month,
            'prevMonth' => $start->copy()->subMonth(),
            'nextMonth' => $start->copy()->addMonth(),
        ]);
    }

    /* 勤怠修正申請フォーム表示 */
    public function showFixForm($id)
    {
        $attendance = Attendance::with(['breakTimes', 'user'])
            ->findOrFail($id);

        $breaks = $attendance->breakTimes->map(function ($break, $index) {
            return (object)[
                'label' => $index === 0 ? '休憩' : '休憩' . ($index + 1),
                'start' => $break->break_start,
                'end'   => $break->break_end,
            ];
        });

        $breaks->push((object)[
            'label' => $attendance->breakTimes->count() === 0 ? '休憩' : '休憩' . ($attendance->breakTimes->count() + 1),
            'start' => null,
            'end'   => null,
        ]);

        $lastRequest = AttendanceCorrectRequest::where('attendance_id', $id)
            ->where('user_id', auth()->id())
            ->latest()
            ->first();

        return view('attendance.fix_request_form', [
            'attendance' => $attendance,
            'breaks'     => $breaks,
            'lastReason' => optional($lastRequest)->reason,
        ]);
    }


    /* 勤怠修正申請処理 */
    public function requestFix(UserStampCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        $fixedClockIn  = $request->input('fixed_clock_in');
        $fixedClockOut = $request->input('fixed_clock_out');
        $inputBreaks = $request->input('fixed_breaks', []);
        $fixedBreaks = [];

        foreach ($inputBreaks as $break) {
            $start = $break['break_start'] ?? null;
            $end   = $break['break_end']   ?? null;

            if ($start || $end) {
                $fixedBreaks[] = [
                    'break_start' => $start ? Carbon::createFromFormat('H:i', $start)->format('H:i') : null,
                    'break_end'   => $end   ? Carbon::createFromFormat('H:i', $end)->format('H:i')   : null,
                ];
            }
        }

        $req = AttendanceCorrectRequest::create([
            'user_id'         => auth()->id(),
            'attendance_id'   => $attendance->id,
            'reason'          => $request->input('reason'),
            'fixed_clock_in'  => $fixedClockIn  ? Carbon::createFromFormat('H:i', $fixedClockIn)  : null,
            'fixed_clock_out' => $fixedClockOut ? Carbon::createFromFormat('H:i', $fixedClockOut) : null,
            'fixed_breaks'    => $fixedBreaks,
            'status'          => 'pending',
        ]);

        return redirect()->route('attendance.fixConfirm', [
            'id'         => $attendance->id,
            'request_id' => $req->id,
        ]);
    }


    /* 修正申請確認画面表示 */
    public function confirmFix($id)
    {
        $attendance = Attendance::with(['breakTimes', 'user'])->findOrFail($id);

        $requestId = request('request_id') ?? session('request_id');

        $submittedRequest = null;
        if ($requestId) {
            $submittedRequest = AttendanceCorrectRequest::where('id', $requestId)
                ->where('attendance_id', $id)
                ->where('user_id', auth()->id())
                ->first();
        }

        if (!$submittedRequest) {
            $submittedRequest = AttendanceCorrectRequest::where('attendance_id', $id)
                ->where('user_id', auth()->id())
                ->latest()
                ->first();
        }

        return view('attendance.fix_request_submitted', [
            'attendance'       => $attendance,
            'submittedRequest' => $submittedRequest,
        ]);
    }

    /* 勤怠修正申請一覧表示（管理者・一般ユーザー共通） */
    public function correctionList()
    {
        $query = AttendanceCorrectRequest::with('user', 'attendance')
            ->orderByDesc('created_at');

        if (!auth()->user()->is_admin) {
            $query->where('user_id', auth()->id());
        }

        $applications = $query->get();

        $pendingApplications = $applications->where('status', 'pending');
        $approvedApplications = $applications->where('status', 'approved');

        return view('stamp_correction_request.list', [
            'pendingApplications' => $pendingApplications,
            'approvedApplications' => $approvedApplications,
        ]);
    }
}

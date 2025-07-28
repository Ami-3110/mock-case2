<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\Application;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Session;

class AttendanceController extends Controller
{
    /* 勤怠登録画面の表示 */
    public function index()
    {
        $user = Auth::user();
        $attendance = Attendance::todayByUser($user->id)->first();
        $status = $attendance?->status ?? '勤務外';

        return view('attendance.index', [
            'status' => $status,
            'date' => now()->format('Y年n月j日（D）'),
            'time' => now()->format('H:i:s'),
        ]);
    }

    /* 出勤打刻 */
    public function clockIn(Request $request)
    {
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

        $start = Carbon::create($year, $month)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendances = Attendance::with(['breakTimes', 'user'])
            ->where('user_id', $user->id)
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

    /* 勤怠修正申請フォームの表示（管理者・一般ユーザー共通） */
    public function show($id)
    {
        $query = Attendance::with('breakTimes', 'user', 'application')
            ->where('id', $id);

        if (!auth()->check() || !auth()->user()->is_admin) {
            $query->where('user_id', auth()->id());
        }

        $attendance = $query->firstOrFail();

        $isEditable = optional($attendance->application)->status !== '承認待ち';

        $layout = (auth()->check() && auth()->user()->is_admin) ? 'layouts.admin' : 'layouts.user';

        return view('attendance.show', [
            'layout' => $layout,
            'attendance' => $attendance,
            'breaks' => $attendance->breakTimes,
            'isEditable' => $isEditable,
        ]);
    }    

    /* 勤怠修正申請の送信（管理者・一般ユーザー共通） */
    public function requestFix(Request $request, $id)
    {
        $query = Attendance::with('breakTimes', 'user', 'application')
        ->where('id', $id);

        // 管理者ならユーザーID絞らず、自分でない場合は自分の勤怠だけに限定
        if (!Auth::user()->is_admin) {
            $query->where('user_id', Auth::id());
        }

        $attendance = $query->firstOrFail();

        $application = $attendance->application ?? new Application();
    
        $application->fill([
            'user_id' => Auth::id(),
            'attendance_id' => $attendance->id,
            'reason' => $request->input('reason'),
            'status' => '承認待ち',
            'fixed_clock_in' => $request->input('fixed_clock_in'),
            'fixed_clock_out' => $request->input('fixed_clock_out'),
            'fixed_break_start' => $request->input('fixed_break_start'),
            'fixed_break_end' => $request->input('fixed_break_end'),
            'fixed_breaks' => json_encode($request->input('breaks', [])),
        ]);
        $application->save();
    
        $isEditable = false;
        $layout = (auth()->check() && auth()->user()->is_admin) ? 'layouts.admin' : 'layouts.user';

        return view('attendance.show', [
            'layout' => $layout,
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

        return view('stamp_correction_request.list', compact('pendingApplications', 'approvedApplications'));
    }
}

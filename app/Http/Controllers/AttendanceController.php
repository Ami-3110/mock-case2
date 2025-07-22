<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Session;


class AttendanceController extends Controller
{
    use AuthorizesRequests;
    /* 勤怠画面の表示 */
    public function status()
    {
        $user = Auth::user();

        $attendance = Attendance::where('user_id', $user->id)
            ->whereDate('work_date', now()->toDateString())
            ->latest()
            ->first();

        if (!$attendance) {
            $status = '勤務外';
        } elseif ($attendance->clock_out) {
            $status = '退勤済み';
        } elseif ($attendance->clock_in) {
            // 休憩中判定は BreakTime テーブル見て判定すればよい
            $break = BreakTime::where('attendance_id', $attendance->id)
                ->whereNull('break_end')
                ->latest()
                ->first();

            $status = $break ? '休憩中' : '勤務中';
        } else {
            $status = '勤務外';
        }

        return view('attendance.status', [
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

        return redirect()->route('attendance.status');
    }

    /* 休憩開始 */
    public function breakStart(Request $request)
    {
        $attendanceId = Session::get('attendance_id');
        if (!$attendanceId) {
            return redirect()->route('attendance.status')->with('error', '出勤していません');
        }

        $break = new BreakTime();
        $break->attendance_id = $attendanceId;
        $break->break_start = now();
        $break->save();

        Session::put('break_time_id', $break->id);

        return redirect()->route('attendance.status');
    }

    /* 休憩終了 */
    public function breakEnd(Request $request)
    {
        $breakTimeId = Session::get('break_time_id');
        if (!$breakTimeId) {
            return redirect()->route('attendance.status')->with('error', '休憩が開始されていません');
        }

        $break = BreakTime::find($breakTimeId);
        if (!$break) {
            return redirect()->route('attendance.status')->with('error', '休憩データが見つかりません');
        }

        $break->break_end = now();
        $break->save();

        Session::forget('break_time_id'); 

        return redirect()->route('attendance.status');
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
            return redirect()->back()->with('error', '出勤記録が見つかりません。');
        }
        if ($attendance->clock_out) {
            return redirect()->back()->with('error', 'すでに退勤しています。');
        }

        $attendance->clock_out = now();
        $attendance->save();

        return redirect()->route('attendance.status');
    }


    /* 当月勤怠一覧表示 */
    public function index()
    {
        return view('attendance.status');
    }

    /* 勤怠修正申請フォームの表示 */
    public function showApplicationForm($attendanceId)
    {
        // 該当勤怠を取得 & viewに渡す
    }

    /* 勤怠修正申請の送信 */
    public function submitApplication(Request $request)
    {
        // attendance_applications テーブルに申請登録
    }

}

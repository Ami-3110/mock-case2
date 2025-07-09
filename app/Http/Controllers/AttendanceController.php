<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceApplication;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    /* 勤怠画面の表示*/
    public function index()
    {
        // ユーザーの当日勤怠取得 or 作成
    }

    /* 出勤打刻 */
    public function clockIn(Request $request)
    {
        // clock_in に現在時刻を保存
    }

    /* 退勤打刻 */
    public function clockOut(Request $request)
    {
        // clock_out に現在時刻を保存
    }

    /* 休憩開始 */
    public function startBreak(Request $request)
    {
        // BreakTime作成 & break_start に現在時刻
    }

    /* 休憩終了 */
    public function endBreak(Request $request)
    {
        // 最後の休憩の break_end に現在時刻
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

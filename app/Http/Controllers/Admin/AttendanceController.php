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
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $staffs = User::where('is_admin', false)->get();

        return view('admin.staff.list', compact('staffs'));
    }

    // 各スタッフの月次勤怠一覧表示 //
    public function staffAttendance(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $attendances = $user->attendances()
        ->whereBetween('work_date', [$start, $end])
        ->orderBy('work_date', 'asc')
        ->get();

        return view('admin.attendance.staff',[
            'user'=> $user,
            'attendances' =>$attendances,
            'year' => $year,
            'month' => $month,
            'prevMonth' => $start->copy()->subMonth(),
            'nextMonth' => $start->copy()->addMonth(),
        ]);
    }

    // CSV出力処理
    public function exportStaffAttendanceCsv(Request $request, $id)
    {
        $year = $request->input('year', now()->year);
        $month = $request->input('month', now()->month);

        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $user = User::findOrFail($id);

        $attendances = $user->attendances()
            ->whereBetween('work_date', [$start, $end])
            ->orderBy('work_date', 'asc')
            ->get();

        $fileName = 'attendance_'.$user->name.$year.sprintf('%02d', $month).'.csv';

        $response = new StreamedResponse(function () use ($attendances) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($handle, ['勤務日', '出勤時刻', '退勤時刻', '休憩時間合計', '勤務時間合計']);

            foreach ($attendances as $attendance) {
                $totalBreakMinutes = $attendance->breakTimes->reduce(function ($carry, $break) {
                    return $carry + $break->break_end->diffInMinutes($break->break_start);
                }, 0);

                if ($attendance->clock_in && $attendance->clock_out) {
                    $workMinutes = $attendance->clock_out->diffInMinutes($attendance->clock_in);
                } else {
                    $workMinutes = 0;
                }

                $actualWorkMinutes = max($workMinutes - $totalBreakMinutes, 0);

                fputcsv($handle, [
                    $attendance->work_date->format('Y-m-d'),
                    $attendance->clock_in ? $attendance->clock_in->format('H:i') : '',
                    $attendance->clock_out ? $attendance->clock_out->format('H:i') : '',
                    $attendance->break_time,
                    $attendance->work_time, 
                ]);                
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', "attachment; filename=\"{$fileName}\"");

        return $response;
    }

    // 承認申請一覧 //
    public function applicationIndex()
    {
        $pendingApplications = AttendanceCorrectRequest::with('user', 'attendance')
            ->where('status', 'pending')
            ->orderByDesc('created_at')
            ->get();

        $approvedApplications = AttendanceCorrectRequest::with('user', 'attendance')
            ->where('status', 'approved')
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
            $attendance->update([
                'clock_in' => $attendanceCorrectRequest->fixed_clock_in,
                'clock_out' => $attendanceCorrectRequest->fixed_clock_out,
            ]);

            $attendance->breakTimes()->delete();

            foreach ($attendanceCorrectRequest->fixed_breaks as $break) {
                $breakStart = Carbon::parse($attendance->work_date . ' ' . $break['break_start']);
                $breakEnd = Carbon::parse($attendance->work_date . ' ' . $break['break_end']);
            
                $attendance->breakTimes()->create([
                    'break_start' => $breakStart,
                    'break_end' => $breakEnd,
                ]);
            }

            $attendanceCorrectRequest->update([
                'status' => 'approved',
            ]);
        });

        $attendanceCorrectRequest->refresh();

        return view('stamp_correction_request.approve', [
            'attendanceCorrectRequest' => $attendanceCorrectRequest,
            'user' => $attendanceCorrectRequest->user,
        ]);
    }  
}

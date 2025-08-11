<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\User;
use App\Models\AttendanceCorrectRequest as AttendanceCorrectRequestModel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Http\Requests\AdminStampCorrectionRequest;

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

    // 勤怠詳細・修正画面表示 //
    public function showFixForm($id)
    {
        $attendance = Attendance::with('breakTimes', 'user')->findOrFail($id);
    
        $breaks = $attendance->breakTimes->map(function ($break, $index) {
            return (object)[
                'label' => $index === 0 ? '休憩' : '休憩' . ($index + 1),
                'start' => $break->break_start,
                'end' => $break->break_end,
            ];
        });
    
        $breaks->push((object)[
            'label' => $attendance->breakTimes->count() === 0 ? '休憩' : '休憩' . ($attendance->breakTimes->count() + 1),
            'start' => null,
            'end' => null,
        ]);
    
    
        return view('admin.attendance.admin_fix', [
            'attendance' => $attendance,
            'breaks' => $breaks,
        ]);
    }

    // 勤怠修正処理 //
    public function submitFixRequest(AdminStampCorrectionRequest $request, $id)
    {
        $attendance = Attendance::with('breakTimes')->findOrFail($id);

        $inputBreaks = $request->input('breaks', []);
        $fixedBreaks = [];

        foreach ($inputBreaks as $index => $break) {
            $start = $break['break_start'] ?? null;
            $end = $break['break_end'] ?? null;

            if ($start === '--:--' && $end === '--:--') {
                continue;
            }

            if ($start && $end) {
                $fixedBreaks[] = [
                    'break_start' => $start,
                    'break_end' => $end,
                ];
                continue;
            }
        }

        AttendanceCorrectRequestModel::create([
            'user_id' => $attendance->user_id,
            'attendance_id' => $attendance->id,
            'reason' => $request->input('reason'),
            'fixed_clock_in' => $request->input('fixed_clock_in'),
            'fixed_clock_out' => $request->input('fixed_clock_out'),
            'fixed_breaks' => $fixedBreaks,
            'status' => 'pending',
        ]);

        return redirect()->route('admin.attendances.showFixForm', ['id' => $attendance->id])->with('success', '修正しました！');
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
 // 修正申請の承認一覧表示（コーチのご指示により増設） //
    public function correctionList(Request $request)
    {
        $query = AttendanceCorrectRequestModel::with(['user', 'attendance'])
            ->orderByDesc('created_at');
    
        // 任意：status=pending/approved で絞りたい時用の簡易フィルタ
        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }
    
        $applications = $query->get();
    
        $pendingApplications  = $applications->where('status', 'pending');
        $approvedApplications = $applications->where('status', 'approved');
    
        return view('stamp_correction_request.list', [
            'pendingApplications'  => $pendingApplications,
            'approvedApplications' => $approvedApplications,
        ]);
    }



 // 修正申請の承認画面表示 //
    public function approveForm(Request $request)
    {
        $acrId = $request->route('attendance_correct_request')
            ?? $request->query('id');
    
        $attendanceCorrectRequest = AttendanceCorrectRequestModel::with(['user','attendance.breakTimes'])
            ->findOrFail($acrId);
    
        return view('stamp_correction_request.approve', [
            'attendanceCorrectRequest' => $attendanceCorrectRequest,
            'user' => $attendanceCorrectRequest->user,
        ]);
    }

   // 修正申請の承認処理 //
    public function approveApplication(Request $request)
    {
        $acrId = $request->route('attendance_correct_request')
            ?? $request->input('id');

        $attendanceCorrectRequest = AttendanceCorrectRequestModel::with('attendance.breakTimes')
            ->findOrFail($acrId);

        $attendance = $attendanceCorrectRequest->attendance;

        DB::transaction(function () use ($attendanceCorrectRequest, $attendance) {
            $attendance->update([
                'clock_in'  => $attendanceCorrectRequest->fixed_clock_in,
                'clock_out' => $attendanceCorrectRequest->fixed_clock_out,
            ]);

            // 休憩再作成
            $attendance->breakTimes()->delete();

            $fixedBreaks = is_string($attendanceCorrectRequest->fixed_breaks)
                ? json_decode($attendanceCorrectRequest->fixed_breaks, true)
                : ($attendanceCorrectRequest->fixed_breaks ?? []);

            $date = Carbon::parse($attendance->work_date)->format('Y-m-d');

            foreach ($fixedBreaks as $break) {
                // どっちか欠けてたらスキップ（念のため防御）
                if (empty($break['break_start']) || empty($break['break_end'])) {
                    continue;
                }
                $attendance->breakTimes()->create([
                    'break_start' => Carbon::parse("$date {$break['break_start']}"),
                    'break_end'   => Carbon::parse("$date {$break['break_end']}"),
                ]);
            }

            $attendanceCorrectRequest->update(['status' => 'approved']);
        });

        $attendanceCorrectRequest->refresh();

        return view('stamp_correction_request.approve', [
            'attendanceCorrectRequest' => $attendanceCorrectRequest,
            'user' => $attendanceCorrectRequest->user,
        ]);
    }
}
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{


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
        // is_approved = false な申請一覧
    }

    // 管理者による承認処理 //
    public function approveApplication($id)
    {
        // is_approved = true, approved_by, approved_at を更新
    }
}

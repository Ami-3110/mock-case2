<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\AttendanceController;


Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
// 会員登録処理
Route::post('/register', [RegisteredUserController::class, 'store']);    
// ログイン画面
Route::get('/login', [AuthenticatedSessionController::class, 'showLoginForm']);
// ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');
// ログアウト処理
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
// メール認証を踏んだらログイン状態、かつステータス（打刻）ページ飛ばす
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('status');
})->middleware(['signed'])->name('verification.verify');

//認証要
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤務画面表示
    Route::get('/attendance/status', [AttendanceController::class, 'status'])->name('attendance.status');
    // 出勤処理
    Route::post('/attendance/start', [AttendanceController::class, 'clockIn'])->name('attendance.start');
    // 休憩開始処理
    Route::post('/break/start', [AttendanceController::class, 'breakStart'])->name('break.start');
    // 休憩終了処理
    Route::post('/break/end', [AttendanceController::class, 'breakEnd'])->name('break.end');
    // 退勤処理
    Route::post('/attendance/end', [AttendanceController::class, 'clockOut'])->name('attendance.end');

    // 勤怠一覧（一般ユーザー）
    Route::get('/attendance/index', [AttendanceController::class, 'index'])->name('attendance.index');
    // 勤怠修正申請（一般ユーザー）
    Route::post('/attendance/fix-request', [AttendanceController::class, 'requestFix'])->name('attendance.fix');




// 管理者専用ルート（middlewareでis_adminチェックするなら別途）
    Route::get('/admin/attendance', [AdminAttendanceController::class, 'index'])->name('admin.attendance');

});
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Auth\EmailVerificationRequest;

use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Admin\Auth\AuthenticatedSessionController as AdminAuthenticatedSessionController;

use App\Http\Controllers\Admin\AttendanceController as AdminAttendanceController;
use App\Http\Controllers\AttendanceController;

use App\Http\Middleware\IsAdmin;

Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
// 会員登録処理
Route::post('/register', [RegisteredUserController::class, 'store']);
// メール認証を踏んだらログイン状態、かつステータス（打刻）ページ飛ばす
Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
    $request->fulfill();
    return redirect()->route('attendance.index');
})->middleware(['signed'])->name('verification.verify');
// ログイン画面
Route::get('/login', [AuthenticatedSessionController::class, 'showLoginForm']);
// ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login');

//認証要
Route::middleware(['auth', 'verified'])->group(function () {
    // 勤怠登録画面
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    // 出勤処理
    Route::post('/attendance/start', [AttendanceController::class, 'clockIn'])->name('attendance.start');
    // 休憩開始処理
    Route::post('/break/start', [AttendanceController::class, 'breakStart'])->name('break.start');
    // 休憩終了処理
    Route::post('/break/end', [AttendanceController::class, 'breakEnd'])->name('break.end');
    // 退勤処理
    Route::post('/attendance/end', [AttendanceController::class, 'clockOut'])->name('attendance.end');
    // 勤怠一覧（一般ユーザー）
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    // 勤怠詳細画面
    Route::get('/attendance/{id}', [AttendanceController::class, 'show'])->name('attendance.show');
    // 勤怠修正申請処理（一般ユーザー）
    Route::post('/attendance/{id}/fix-request', [AttendanceController::class, 'requestFix'])->name('attendance.fix');
    // 勤怠修正申請一覧
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'correctionList'])->name('stamp_correction_request.list');
    // ログアウト処理
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// 管理者専用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])
        ->name('login');
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store']);

    Route::middleware(['auth', 'verified', IsAdmin::class])->group(function () {
        // 管理者：日別勤怠一覧（全体）
        Route::get('/attendance/list/{date?}', [AdminAttendanceController::class, 'list'])->name('attendance.list');
        // 管理者：勤怠詳細
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->name('attendance.show');
        // 管理者：スタッフ一覧
        Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
        // 管理者：スタッフ別勤怠一覧
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');
        // 管理者：勤怠修正申請一覧
        Route::get('/stamp_correction_request/list', [AttendanceController::class, 'correctionList'])->name('stamp_correction_request.list');
        // 管理者：修正申請承認画面
        Route::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceController::class, 'approveForm'])->name('admin.correction.approve');
        // ログアウト処理
        Route::post('/logout', [\App\Http\Controllers\Admin\Auth\AuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});
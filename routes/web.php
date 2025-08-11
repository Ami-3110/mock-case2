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
Route::get('/login', [AuthenticatedSessionController::class, 'showLoginForm'])->name('login');
// ログイン処理
Route::post('/login', [AuthenticatedSessionController::class, 'store']);



//要認証・一般ユーザールート
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
    // 勤怠一覧画面
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    // 勤怠修正申請フォーム画面
    Route::get('/attendance/{id}', [AttendanceController::class, 'showFixForm'])->name('attendance.fixForm');
    // 勤怠修正申請処理
    Route::post('/attendance/{id}/fix-request', [AttendanceController::class, 'requestFix'])->name('attendance.fix');
    // 勤怠修正申請確認画面
    Route::get('/attendance/{id}/fix-confirm', [AttendanceController::class, 'confirmFix'])->name('attendance.fixConfirm');
    // 勤怠修正申請一覧（コーチのご指示により管理者を専用ルートへ移行）
    Route::get('/stamp_correction_request/list', [AttendanceController::class, 'correctionList'])->name('stamp_correction_request.list');
    // ログアウト処理
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});

// 要認証・管理者専用ルート
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AdminAuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AdminAuthenticatedSessionController::class, 'store']);

    Route::middleware(['auth', IsAdmin::class])->group(function () {
        // 管理者：日別勤怠一覧
        Route::get('/attendance/list/{date?}', [AdminAttendanceController::class, 'list'])->name('attendance.list');
        // 管理者：勤怠詳細・修正画面表示
        Route::get('/attendances/{id}/fix', [AdminAttendanceController::class, 'showFixForm'])->name('attendances.showFixForm');
        // 管理者：勤怠修正処理
        Route::post('/attendances/{id}/fix', [AdminAttendanceController::class, 'submitFixRequest'])->name('attendances.fix');
        // 管理者：スタッフ一覧
        Route::get('/staff/list', [AdminAttendanceController::class, 'staffList'])->name('staff.list');
        // 管理者：スタッフ別勤怠一覧
        Route::get('/attendance/staff/{id}', [AdminAttendanceController::class, 'staffAttendance'])->name('attendance.staff');
        // 管理者：勤怠修正申請一覧
        Route::get('/requests', [AdminAttendanceController::class, 'correctionList'])->name('requests.index');
        // 管理者：修正申請承認画面
        Route::get('/requests/{attendance_correct_request}', [AdminAttendanceController::class, 'approveForm'])
        ->name('requests.show');
        //コーチのご指示により差替えRoute::get('/stamp_correction_request/approve/{attendance_correct_request}', [AdminAttendanceController::class, 'approveForm'])->name('correction.approve');
        // 管理者：修正申請承認処理
        Route::post('/requests/{attendance_correct_request}', [AdminAttendanceController::class, 'approveApplication'])
        ->name('requests.update');
        // CSV出力
        Route::get('/attendance/staff/{id}/csv', [AdminAttendanceController::class, 'exportStaffAttendanceCsv'])->name('attendance.staff.csv');

        // ログアウト処理
        Route::post('/logout', [AdminAuthenticatedSessionController::class, 'destroy'])
            ->name('logout');
    });
});




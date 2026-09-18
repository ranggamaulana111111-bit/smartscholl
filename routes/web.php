<?php

use App\Http\Controllers\AcademicYearController;
use App\Http\Controllers\AssessmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EarlyWarningController;
use App\Http\Controllers\JournalController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\ParentDashboardController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Super Admin & Admin Sekolah
    Route::middleware('role:super_admin,admin_sekolah')->group(function () {
        Route::post('students/{student}/regenerate-qr', [StudentController::class, 'regenerateQr'])->name('students.regenerate-qr');
        Route::resource('students', StudentController::class);
        Route::resource('teachers', TeacherController::class);
        Route::resource('rombels', RombelController::class);
        Route::resource('academic-years', AcademicYearController::class);
        Route::resource('subjects', SubjectController::class);
    });

    // Monitoring & Rapor — dibuka untuk semua peran (akses disaring di controller)
    Route::prefix('students')->name('students.')->middleware('role:super_admin,admin_sekolah,guru,siswa,orang_tua')->group(function (): void {
        Route::get('{student}/progress', [StudentController::class, 'progress'])->name('progress');
        Route::get('{student}/rapor', [StudentController::class, 'rapor'])->name('rapor');
    });

    // Absensi — Admin & Guru bisa manage, Guru bisa scan
    Route::prefix('attendance')->name('attendance.')->group(function (): void {
        Route::get('/', [AttendanceController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('scan', [AttendanceController::class, 'scan'])->name('scan')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('scan', [AttendanceController::class, 'record'])->name('record')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('scan/bulk', [AttendanceController::class, 'recordBulk'])->name('recordBulk')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('qrcodes', [AttendanceController::class, 'qrCodes'])->name('qrcodes')
            ->middleware('role:super_admin,admin_sekolah');
        Route::get('{student}/qrcode', [AttendanceController::class, 'qrCode'])->name('qrcode')
            ->middleware('role:super_admin,admin_sekolah');
        Route::get('manual/schedule/{schedule}', [AttendanceController::class, 'manualSchedule'])->name('manualSchedule')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('manual', [AttendanceController::class, 'manual'])->name('manual')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('manual', [AttendanceController::class, 'storeManual'])->name('storeManual')
            ->middleware('role:super_admin,admin_sekolah,guru');
    });

    // Jadwal Mengajar — Admin & Guru
    Route::prefix('schedules')->name('schedules.')->group(function (): void {
        Route::get('/', [ScheduleController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('create', [ScheduleController::class, 'create'])->name('create')
            ->middleware('role:super_admin,admin_sekolah');
        Route::post('/', [ScheduleController::class, 'store'])->name('store')
            ->middleware('role:super_admin,admin_sekolah');
        Route::get('{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit')
            ->middleware('role:super_admin,admin_sekolah');
        Route::put('{schedule}', [ScheduleController::class, 'update'])->name('update')
            ->middleware('role:super_admin,admin_sekolah');
        Route::delete('{schedule}', [ScheduleController::class, 'destroy'])->name('destroy')
            ->middleware('role:super_admin,admin_sekolah');
    });

    // Jurnal KBM — Guru & Admin
    Route::prefix('journals')->name('journals.')->group(function (): void {
        Route::get('/', [JournalController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('create', [JournalController::class, 'create'])->name('create')
            ->middleware('role:guru');
        Route::post('/', [JournalController::class, 'store'])->name('store')
            ->middleware('role:guru');
        Route::get('{journal}', [JournalController::class, 'show'])->name('show')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('{journal}/edit', [JournalController::class, 'edit'])->name('edit')
            ->middleware('role:guru');
        Route::put('{journal}', [JournalController::class, 'update'])->name('update')
            ->middleware('role:guru');
    });

    // Nilai & Penilaian — Guru & Admin
    Route::prefix('assessments')->name('assessments.')->group(function (): void {
        Route::get('/', [AssessmentController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru,siswa');
        Route::get('create', [AssessmentController::class, 'create'])->name('create')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('/', [AssessmentController::class, 'store'])->name('store')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('{assessment}/template', [AssessmentController::class, 'template'])->name('template')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('{assessment}/export', [AssessmentController::class, 'export'])->name('export')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('{assessment}/import', [AssessmentController::class, 'import'])->name('import')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('{assessment}/grades', [AssessmentController::class, 'storeGrades'])->name('grades')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('{assessment}', [AssessmentController::class, 'show'])->name('show')
            ->middleware('role:super_admin,admin_sekolah,guru,siswa');
        Route::get('{assessment}/edit', [AssessmentController::class, 'edit'])->name('edit')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::put('{assessment}', [AssessmentController::class, 'update'])->name('update')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::delete('{assessment}', [AssessmentController::class, 'destroy'])->name('destroy')
            ->middleware('role:super_admin,admin_sekolah,guru');
    });

    // Tugas & PR — Guru buat, Siswa kumpul, Admin/Guru/Ortu lihat
    Route::prefix('assignments')->name('assignments.')->group(function (): void {
        Route::get('/', [AssignmentController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru,siswa,orang_tua');
        Route::get('create', [AssignmentController::class, 'create'])->name('create')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('/', [AssignmentController::class, 'store'])->name('store')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::get('{assignment}', [AssignmentController::class, 'show'])->name('show')
            ->middleware('role:super_admin,admin_sekolah,guru,siswa');
        Route::get('{assignment}/download', [AssignmentController::class, 'download'])->name('download')
            ->middleware('role:super_admin,admin_sekolah,guru,siswa');
        Route::post('{assignment}/submit', [AssignmentController::class, 'submit'])->name('submit')
            ->middleware('role:siswa');
        Route::delete('{assignment}', [AssignmentController::class, 'destroy'])->name('destroy')
            ->middleware('role:super_admin,admin_sekolah,guru');
    });

    // Portal Orang Tua — Orang Tua
    Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard')
        ->middleware('role:orang_tua');

    // Early Warning System — Admin & Guru BK
    Route::prefix('ews')->name('ews.')->group(function (): void {
        Route::get('/', [EarlyWarningController::class, 'index'])->name('index')
            ->middleware('role:super_admin,admin_sekolah,guru');
        Route::post('{earlyWarningLog}/resolve', [EarlyWarningController::class, 'resolve'])->name('resolve')
            ->middleware('role:super_admin,admin_sekolah');
        Route::post('/run-check', [EarlyWarningController::class, 'runCheck'])->name('runCheck')
            ->middleware('role:super_admin,admin_sekolah');
    });

    // Audit Trail — hanya Admin
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')
        ->middleware('role:super_admin,admin_sekolah');
});

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
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RombelController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TeacherController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/**
 * Setiap modul punya dua lapis gerbang: `role:` menentukan bentuk akses siapa
 * yang boleh masuk, `permission:` menentukan apakah akses itu masih diberikan
 * matriks hak akses. Keduanya harus terpisah, bukan digabung pada satu
 * middleware, karena `role:a,b,permission:x` akan menelan `permission:x`
 * menjadi parameter role dan membiarkan modul terbuka.
 *
 * Super Admin melewati `permission:` secara otomatis, jadi matriks tidak
 * pernah bisa mengunci administrator platform keluar dari sistem.
 */

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return view('welcome');
})->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

Route::middleware(['auth', 'tenant'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard')
        ->middleware('permission:dashboard.view');

    // Super Admin & Admin Sekolah
    Route::middleware('role:super_admin,admin_sekolah')->group(function () {
        Route::post('students/{student}/regenerate-qr', [StudentController::class, 'regenerateQr'])
            ->name('students.regenerate-qr')
            ->middleware('permission:students.manage');

        Route::resource('students', StudentController::class)
            ->middleware('permission:students.manage');
        Route::resource('teachers', TeacherController::class)
            ->middleware('permission:teachers.manage');
        Route::resource('rombels', RombelController::class)
            ->middleware('permission:rombels.manage');
        Route::resource('academic-years', AcademicYearController::class)
            ->middleware('permission:academic_years.manage');
        Route::resource('subjects', SubjectController::class)
            ->middleware('permission:subjects.manage');
    });

    // Monitoring & Rapor — dibuka untuk semua peran (akses disaring di controller)
    Route::prefix('students')->name('students.')->middleware('role:super_admin,admin_sekolah,guru,siswa,orang_tua')->group(function (): void {
        Route::get('{student}/progress', [StudentController::class, 'progress'])->name('progress');
        Route::get('{student}/rapor', [StudentController::class, 'rapor'])->name('rapor');
    })->middleware('permission:students.view');

    // Absensi — Admin & Guru bisa manage, Guru bisa scan
    Route::prefix('attendance')->name('attendance.')->group(function (): void {
        Route::get('/', [AttendanceController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.view']);
        Route::get('scan', [AttendanceController::class, 'scan'])->name('scan')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.scan']);
        Route::post('scan', [AttendanceController::class, 'record'])->name('record')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.scan', 'throttle:90,1']);
        Route::post('scan/bulk', [AttendanceController::class, 'recordBulk'])->name('recordBulk')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.scan', 'throttle:20,1']);
        Route::get('qrcodes', [AttendanceController::class, 'qrCodes'])->name('qrcodes')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.qrcodes']);
        Route::get('{student}/qrcode', [AttendanceController::class, 'qrCode'])->name('qrcode')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.qrcodes']);

        // Rekap harian per rombel & ekspor CSV. Guru melihat rombel binaannya saja.
        Route::get('recap', [AttendanceController::class, 'recap'])->name('recap')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.view']);
        Route::get('recap/export', [AttendanceController::class, 'exportRecap'])->name('recap.export')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.view']);

        // Riwayat jejak pemindaian: kode tak dikenal, kartu di luar kelas,
        // scan di luar jam pelajaran, dan scan ganda.
        Route::get('scan-events', [AttendanceController::class, 'scanEvents'])->name('scan-events')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:attendance.audit']);

        Route::prefix('schedule/{schedule}/scan')->name('schedule.scan.')->group(function (): void {
            Route::get('/', [AttendanceController::class, 'scanLesson'])->name('index')
                ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.scan']);
            Route::get('status', [AttendanceController::class, 'scanLessonStatus'])->name('status')
                ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.scan', 'throttle:240,1']);
            Route::post('remaining', [AttendanceController::class, 'markRemaining'])->name('remaining')
                ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual']);
            Route::post('mark', [AttendanceController::class, 'markStudent'])->name('mark')
                ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual', 'throttle:180,1']);
        });

        Route::get('manual/schedule/{schedule}/pertemuan', [AttendanceController::class, 'manualMeetings'])->name('manualMeetings')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual']);
        Route::get('manual/schedule/{schedule}', [AttendanceController::class, 'manualSchedule'])->name('manualSchedule')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual']);
        Route::get('manual', [AttendanceController::class, 'manual'])->name('manual')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual']);
        Route::post('manual', [AttendanceController::class, 'storeManual'])->name('storeManual')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:attendance.manual', 'throttle:180,1']);
    });

    // Jadwal Mengajar — Admin & Guru
    Route::prefix('schedules')->name('schedules.')->group(function (): void {
        Route::get('/', [ScheduleController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:schedules.view']);
        Route::get('create', [ScheduleController::class, 'create'])->name('create')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:schedules.manage']);
        Route::post('/', [ScheduleController::class, 'store'])->name('store')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:schedules.manage']);
        Route::get('{schedule}/edit', [ScheduleController::class, 'edit'])->name('edit')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:schedules.manage']);
        Route::put('{schedule}', [ScheduleController::class, 'update'])->name('update')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:schedules.manage']);
        Route::delete('{schedule}', [ScheduleController::class, 'destroy'])->name('destroy')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:schedules.manage']);
    });

    // Jurnal KBM — Guru & Admin
    Route::prefix('journals')->name('journals.')->group(function (): void {
        Route::get('/', [JournalController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:journals.view']);
        Route::get('create', [JournalController::class, 'create'])->name('create')
            ->middleware(['role:guru', 'permission:journals.manage']);
        Route::post('/', [JournalController::class, 'store'])->name('store')
            ->middleware(['role:guru', 'permission:journals.manage']);
        Route::get('{journal}', [JournalController::class, 'show'])->name('show')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:journals.view']);
        Route::get('{journal}/edit', [JournalController::class, 'edit'])->name('edit')
            ->middleware(['role:guru', 'permission:journals.manage']);
        Route::put('{journal}', [JournalController::class, 'update'])->name('update')
            ->middleware(['role:guru', 'permission:journals.manage']);
    });

    // Nilai & Penilaian — Guru & Admin
    Route::prefix('assessments')->name('assessments.')->group(function (): void {
        Route::get('/', [AssessmentController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru,siswa', 'permission:assessments.view']);
        Route::get('create', [AssessmentController::class, 'create'])->name('create')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::post('/', [AssessmentController::class, 'store'])->name('store')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::get('{assessment}/template', [AssessmentController::class, 'template'])->name('template')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::get('{assessment}/export', [AssessmentController::class, 'export'])->name('export')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::post('{assessment}/import', [AssessmentController::class, 'import'])->name('import')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::post('{assessment}/grades', [AssessmentController::class, 'storeGrades'])->name('grades')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::get('{assessment}', [AssessmentController::class, 'show'])->name('show')
            ->middleware(['role:super_admin,admin_sekolah,guru,siswa', 'permission:assessments.view']);
        Route::get('{assessment}/edit', [AssessmentController::class, 'edit'])->name('edit')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::put('{assessment}', [AssessmentController::class, 'update'])->name('update')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
        Route::delete('{assessment}', [AssessmentController::class, 'destroy'])->name('destroy')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assessments.manage']);
    });

    // Tugas & PR — Guru buat, Siswa kumpul, Admin/Guru/Ortu lihat
    Route::prefix('assignments')->name('assignments.')->group(function (): void {
        Route::get('/', [AssignmentController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru,siswa,orang_tua', 'permission:assignments.view']);
        Route::get('create', [AssignmentController::class, 'create'])->name('create')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assignments.manage']);
        Route::post('/', [AssignmentController::class, 'store'])->name('store')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assignments.manage']);
        Route::get('{assignment}', [AssignmentController::class, 'show'])->name('show')
            ->middleware(['role:super_admin,admin_sekolah,guru,siswa', 'permission:assignments.view']);
        Route::get('{assignment}/download', [AssignmentController::class, 'download'])->name('download')
            ->middleware(['role:super_admin,admin_sekolah,guru,siswa', 'permission:assignments.view']);
        Route::post('{assignment}/submit', [AssignmentController::class, 'submit'])->name('submit')
            ->middleware(['role:siswa', 'permission:assignments.submit']);
        Route::delete('{assignment}', [AssignmentController::class, 'destroy'])->name('destroy')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:assignments.manage']);
    });

    // Portal Orang Tua — Orang Tua
    Route::get('/parent/dashboard', [ParentDashboardController::class, 'index'])->name('parent.dashboard')
        ->middleware(['role:orang_tua', 'permission:parent.dashboard']);

    // Early Warning System — Admin & Guru BK
    Route::prefix('ews')->name('ews.')->group(function (): void {
        Route::get('/', [EarlyWarningController::class, 'index'])->name('index')
            ->middleware(['role:super_admin,admin_sekolah,guru', 'permission:ews.view']);
        Route::post('{earlyWarningLog}/resolve', [EarlyWarningController::class, 'resolve'])->name('resolve')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:ews.manage']);
        Route::post('/run-check', [EarlyWarningController::class, 'runCheck'])->name('runCheck')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:ews.manage']);
    });

    // Audit Trail — hanya Admin
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index')
        ->middleware(['role:super_admin,admin_sekolah', 'permission:audit.view']);

    // Pengaturan — Admin & Super Admin (tab Sistem khusus Super Admin)
    Route::prefix('settings')->name('settings.')->middleware('role:super_admin,admin_sekolah')->group(function (): void {
        Route::get('/', [SettingsController::class, 'index'])->name('index')
            ->middleware('permission:settings.view');
        Route::post('/', [SettingsController::class, 'update'])->name('update')
            ->middleware('permission:settings.school,settings.system');
    });

    // Manajemen Pengguna — Super Admin & Admin Sekolah (butuh izin users.manage)
    Route::prefix('users')->name('users.')->middleware(['role:super_admin,admin_sekolah', 'permission:users.manage'])->group(function (): void {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');

        Route::put('/{user}/permissions', [UserController::class, 'updatePermissions'])
            ->name('permissions.update')
            ->middleware('permission:permissions.manage');
    });

    // Matriks Hak Akses — Super Admin & Admin Sekolah
    Route::prefix('permissions')->name('permissions.')->group(function (): void {
        Route::get('/', [PermissionController::class, 'index'])
            ->name('index')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:permissions.view']);

        Route::put('/', [PermissionController::class, 'update'])
            ->name('update')
            ->middleware(['role:super_admin,admin_sekolah', 'permission:permissions.manage']);
    });
});

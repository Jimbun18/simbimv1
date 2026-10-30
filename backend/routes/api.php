<?php

use App\Http\Controllers\Api\AcademicYearController;
use App\Http\Controllers\Api\ActivityLogController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AssetController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BackupController;
use App\Http\Controllers\Api\ClassController;
use App\Http\Controllers\Api\DailyActivityController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\FeeTypeController;
use App\Http\Controllers\Api\FinanceTransactionController;
use App\Http\Controllers\Api\GuardianController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\InAppNotificationController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\LearningProgressController;
use App\Http\Controllers\Api\ParentPortalController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ReferenceController;
use App\Http\Controllers\Api\ReportCardController;
use App\Http\Controllers\Api\RoomController;
use App\Http\Controllers\Api\ScheduleController;
use App\Http\Controllers\Api\SchoolCalendarController;
use App\Http\Controllers\Api\SchoolProfileController;
use App\Http\Controllers\Api\SemesterController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\StudentAttendanceController;
use App\Http\Controllers\Api\StudentController;
use App\Http\Controllers\Api\SubjectController;
use App\Http\Controllers\Api\TeacherAssignmentController;
use App\Http\Controllers\Api\TeacherAttendanceController;
use App\Http\Controllers\Api\TeacherController;
use App\Http\Controllers\Api\TeachingEvidenceController;
use App\Http\Controllers\Api\TimeSlotController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\CmsController;
use App\Http\Controllers\Api\PpdbController;
use App\Http\Controllers\Api\StudentObservationController;
use App\Http\Controllers\Api\BkMonitoringController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public routes
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::get('/public/galleries', [CmsController::class, 'getPublicGalleries']);
    Route::get('/public/achievements', [CmsController::class, 'getPublicAchievements']);
    Route::get('/public/articles', [CmsController::class, 'getPublicArticles']);
    Route::get('/public/articles/{id}', [CmsController::class, 'getPublicArticleDetail']);
    Route::get('/public/teachers', [CmsController::class, 'getPublicTeachers']);
    Route::post('/public/ppdb/register', [PpdbController::class, 'submit']);
    Route::get('/public/ppdb/status/{nomor}', [PpdbController::class, 'checkStatus']);

    // Protected routes (Sanctum)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth & Profile
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::match(['put', 'post'], '/me', [AuthController::class, 'updateProfile']);
        Route::delete('/me/photo', [AuthController::class, 'deletePhoto']);
        Route::put('/me/password', [AuthController::class, 'changePassword']);

        // References (all authenticated users)
        Route::get('/references', [ReferenceController::class, 'index']);

        // School Profile (Read: all, Update: Admin)
        Route::get('/school-profile', [SchoolProfileController::class, 'show']);

        // Master Data Read Access (Guru can read classes, students, schedules)
        Route::get('/students/export', [StudentController::class, 'exportExcel']);
        Route::get('/students', [StudentController::class, 'index']);
        Route::get('/students/{student}', [StudentController::class, 'show']);
        Route::get('/teachers/export', [TeacherController::class, 'exportExcel']);
        Route::get('/teachers', [TeacherController::class, 'index']);
        Route::get('/teachers/{teacher}', [TeacherController::class, 'show']);
        Route::get('/classes/export', [ClassController::class, 'exportExcel']);
        Route::get('/classes', [ClassController::class, 'index']);
        Route::get('/classes/{class}', [ClassController::class, 'show']);
        Route::get('/schedules', [ScheduleController::class, 'index']);
        Route::get('/schedules/{schedule}', [ScheduleController::class, 'show']);
        Route::get('/academic-years', [AcademicYearController::class, 'index']);
        Route::get('/semesters', [SemesterController::class, 'index']);
        Route::get('/time-slots', [TimeSlotController::class, 'index']);
        Route::get('/subjects', [SubjectController::class, 'index']);
        Route::get('/rooms', [RoomController::class, 'index']);
        Route::get('/rooms/{room}', [RoomController::class, 'show']);
        Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index']);

        // System Settings (Read: all authenticated)
        Route::get('/settings', [SettingController::class, 'index']);

        // Teacher & Staff Attendance (FR-ABS-01 s/d FR-ABS-03, FR-ABS-08, FR-ABS-10)
        Route::prefix('attendance/teachers')->group(function () {
            Route::get('/today', [TeacherAttendanceController::class, 'today']);
            Route::post('/check-in', [TeacherAttendanceController::class, 'checkIn']);
            Route::post('/check-out', [TeacherAttendanceController::class, 'checkOut']);
            Route::post('/leave', [TeacherAttendanceController::class, 'leave']);
            Route::get('/recap/export', [TeacherAttendanceController::class, 'exportRecap']);
            Route::get('/recap', [TeacherAttendanceController::class, 'recap']);
            Route::get('/', [TeacherAttendanceController::class, 'index']);
        });

        // Teaching Evidence (FR-ABS-05, FR-ABS-06)
        Route::prefix('attendance/evidence')->group(function () {
            Route::get('/', [TeachingEvidenceController::class, 'index']);
            Route::post('/', [TeachingEvidenceController::class, 'store']);
            Route::get('/{id}', [TeachingEvidenceController::class, 'show']);
            Route::delete('/{id}', [TeachingEvidenceController::class, 'destroy']);
        });

        // Student Attendance (FR-ABS-04, FR-ABS-07, FR-ABS-08)
        Route::prefix('attendance/students')->group(function () {
            Route::get('/homeroom-monitoring', [StudentAttendanceController::class, 'homeroomMonitoring']);
            Route::get('/session-sheet', [StudentAttendanceController::class, 'sessionSheet']);
            Route::get('/recap-class', [StudentAttendanceController::class, 'classRecap']);
            Route::get('/recap-class/export', [StudentAttendanceController::class, 'exportClassRecap']);
            Route::get('/', [StudentAttendanceController::class, 'index']);
            Route::post('/', [StudentAttendanceController::class, 'store']);
            Route::put('/{id}', [StudentAttendanceController::class, 'update']);
        });

        // Daily Activities (FR-PRG-01, FR-PRG-03, FR-PRG-04)
        Route::apiResource('activities', DailyActivityController::class);

        // Learning Progress & Tahfidz/Character (FR-PRG-02, FR-PRG-04, FR-PRG-05, FR-PRG-06)
        Route::get('/progress', [LearningProgressController::class, 'index']);
        Route::post('/progress', [LearningProgressController::class, 'store']);
        Route::post('/progress/batch', [LearningProgressController::class, 'batchStore']);
        Route::get('/progress/summary/{student}', [LearningProgressController::class, 'summary']);
        Route::get('/progress/{id}', [LearningProgressController::class, 'show']);
        Route::put('/progress/{id}', [LearningProgressController::class, 'update']);
        Route::delete('/progress/{id}', [LearningProgressController::class, 'destroy']);

        // Announcements (Read: All matching target_role)
        Route::get('/announcements', [AnnouncementController::class, 'index']);
        Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

        // Catatan Observasi Perilaku Siswa (Bisa Diakses Semua Guru)
        Route::get('/observations', [StudentObservationController::class, 'index']);
        Route::post('/observations', [StudentObservationController::class, 'store']);
        Route::get('/observations/{observation}', [StudentObservationController::class, 'show']);
        Route::put('/observations/{observation}', [StudentObservationController::class, 'update']);
        Route::delete('/observations/{observation}', [StudentObservationController::class, 'destroy']);
        Route::post('/observations/{observation}/feedback-bk', [StudentObservationController::class, 'addFeedbackBk']);
        Route::post('/observations/{observation}/escalate-to-bk', [StudentObservationController::class, 'escalateToBk']);

        // Analisis & Monitoring BK (Eksklusif Khusus Guru BK)
        Route::prefix('bk')->group(function () {
            Route::get('/dashboard', [BkMonitoringController::class, 'dashboard']);
            Route::get('/cases', [BkMonitoringController::class, 'indexCases']);
            Route::post('/cases', [BkMonitoringController::class, 'storeCase']);
            Route::get('/cases/{counselingCase}', [BkMonitoringController::class, 'showCase']);
            Route::put('/cases/{counselingCase}', [BkMonitoringController::class, 'updateCase']);
            Route::delete('/cases/{counselingCase}', [BkMonitoringController::class, 'destroyCase']);
            Route::post('/cases/{counselingCase}/logs', [BkMonitoringController::class, 'storeLog']);
            Route::put('/logs/{log}', [BkMonitoringController::class, 'updateLog']);
            Route::delete('/logs/{log}', [BkMonitoringController::class, 'destroyLog']);
            Route::get('/export', [BkMonitoringController::class, 'exportExcel']);
        });

        // In-App Notifications (FR-NTF-01)
        Route::get('/notifications', [InAppNotificationController::class, 'index']);
        Route::post('/notifications/{id}/read', [InAppNotificationController::class, 'markAsRead']);
        Route::post('/notifications/read-all', [InAppNotificationController::class, 'markAllAsRead']);

        // Guardian Portal (FR-PRG-04, FR-PRG-05, FR-ABS-11, FR-RAPOR)
        Route::get('/parent/children', [ParentPortalController::class, 'children']);
        Route::get('/parent/children/{id}/summary', [ParentPortalController::class, 'childSummary']);
        Route::get('/parent/children/{id}/attendance', [StudentAttendanceController::class, 'childAttendance']);
        Route::get('/parent/children/{id}/report-cards', [ParentPortalController::class, 'childReportCards']);
        Route::get('/parent/report-cards/{id}', [ParentPortalController::class, 'childReportCards']);

        // Invoices & Payments (Read: Wali for their children, Admin for all - FR-KEU-07)
        Route::get('/invoices', [InvoiceController::class, 'index']);
        Route::get('/invoices/{id}', [InvoiceController::class, 'show']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::get('/payments/{id}', [PaymentController::class, 'show']);
        Route::get('/payments/{id}/receipt', [PaymentController::class, 'receipt']);

        // School Calendar & Holidays (All Authenticated Roles)
        Route::get('/calendar', [SchoolCalendarController::class, 'index']);
        Route::get('/calendar/check-date', [SchoolCalendarController::class, 'checkDate']);

        // Admin Only (Kepala Sekolah & Staf Administrasi)
        Route::middleware('role:kepala_sekolah,staf_admin')->group(function () {
            // School Calendar Management (Admin)
            Route::post('/calendar', [SchoolCalendarController::class, 'store']);
            Route::put('/calendar/{id}', [SchoolCalendarController::class, 'update']);
            Route::delete('/calendar/{id}', [SchoolCalendarController::class, 'destroy']);
            Route::post('/calendar/seed-template', [SchoolCalendarController::class, 'seedTemplate']);
            // School Profile & Academic Year
            Route::put('/school-profile', [SchoolProfileController::class, 'update']);
            Route::post('/academic-years', [AcademicYearController::class, 'store']);
            Route::get('/academic-years/{academic_year}', [AcademicYearController::class, 'show']);
            Route::put('/academic-years/{academic_year}', [AcademicYearController::class, 'update']);
            Route::delete('/academic-years/{academic_year}', [AcademicYearController::class, 'destroy']);
            Route::post('/academic-years/{academic_year}/set-active', [AcademicYearController::class, 'setActive']);

            // Semesters Management & Period Lock
            Route::get('/semesters', [SemesterController::class, 'index']);
            Route::get('/semesters/{semester}', [SemesterController::class, 'show']);
            Route::post('/semesters', [SemesterController::class, 'store']);
            Route::put('/semesters/{semester}', [SemesterController::class, 'update']);
            Route::post('/semesters/{semester}/activate', [SemesterController::class, 'activate']);
            Route::get('/semesters/{semester}/checklist', [SemesterController::class, 'checklist']);
            Route::post('/semesters/{semester}/close', [SemesterController::class, 'close']);
            Route::post('/semesters/{semester}/reopen', [SemesterController::class, 'reopen']);

            // Rooms & Subjects
            Route::apiResource('rooms', RoomController::class);
            Route::post('/subjects', [SubjectController::class, 'store']);
            Route::get('/subjects/{subject}', [SubjectController::class, 'show']);
            Route::put('/subjects/{subject}', [SubjectController::class, 'update']);
            Route::delete('/subjects/{subject}', [SubjectController::class, 'destroy']);

            // Teachers & Staff
            Route::post('/teachers', [TeacherController::class, 'store']);
            Route::match(['put', 'post'], '/teachers/{teacher}', [TeacherController::class, 'update']);
            Route::delete('/teachers/{teacher}', [TeacherController::class, 'destroy']);
            Route::delete('/teachers/{teacher}/photo', [TeacherController::class, 'deletePhoto']);
            Route::post('/teachers/{teacher}/documents', [TeacherController::class, 'uploadDocument']);
            Route::delete('/teachers/{teacher}/documents/{document}', [TeacherController::class, 'deleteDocument']);
            Route::apiResource('staff', StaffController::class);

            // Classes (Write)
            Route::post('/classes', [ClassController::class, 'store']);
            Route::put('/classes/{class}', [ClassController::class, 'update']);
            Route::delete('/classes/{class}', [ClassController::class, 'destroy']);
            Route::post('/classes/promote-students', [ClassController::class, 'promoteStudents']);

            // Guardians & Students (Write)
            Route::apiResource('guardians', GuardianController::class);
            Route::post('/guardians/{guardian}/sync-students', [GuardianController::class, 'syncStudents']);
            Route::post('/guardians/{guardian}/create-account', [GuardianController::class, 'createAccount']);
            Route::post('/students', [StudentController::class, 'store']);
            Route::match(['put', 'post'], '/students/{student}', [StudentController::class, 'update']);
            Route::delete('/students/{student}', [StudentController::class, 'destroy']);
            Route::delete('/students/{student}/photo', [StudentController::class, 'deletePhoto']);
            Route::post('/students/{student}/guardians', [StudentController::class, 'syncGuardians']);
            Route::post('/students/{student}/status', [StudentController::class, 'updateStatus']);

            // Time Slots, Teacher Assignments, Schedules (Write)
            Route::post('/time-slots', [TimeSlotController::class, 'store']);
            Route::get('/time-slots/{time_slot}', [TimeSlotController::class, 'show']);
            Route::put('/time-slots/{time_slot}', [TimeSlotController::class, 'update']);
            Route::delete('/time-slots/{time_slot}', [TimeSlotController::class, 'destroy']);
            Route::post('/teacher-assignments/sync-to-schedules', [TeacherAssignmentController::class, 'syncAllToSchedules']);
            Route::apiResource('teacher-assignments', TeacherAssignmentController::class);
            Route::post('/schedules', [ScheduleController::class, 'store']);
            Route::post('/schedules/quick-set', [ScheduleController::class, 'quickSet']);
            Route::post('/schedules/clear-class', [ScheduleController::class, 'clearClassSchedules']);
            Route::post('/schedules/apply-template', [ScheduleController::class, 'applyOfficialTemplate']);
            Route::put('/schedules/{schedule}', [ScheduleController::class, 'update']);
            Route::delete('/schedules/{schedule}', [ScheduleController::class, 'destroy']);

            // Excel Import Engine (FR-IMP-01 s/d FR-IMP-08)
            Route::prefix('imports')->group(function () {
                Route::get('/templates/{jenis}', [ImportController::class, 'downloadTemplate']);
                Route::post('/{jenis}/validate', [ImportController::class, 'validateFile']);
                Route::post('/{batch}/commit', [ImportController::class, 'commit']);
                Route::post('/{batch}/rollback', [ImportController::class, 'rollback']);
                Route::get('/{batch}/errors/export', [ImportController::class, 'exportErrors']);
                Route::get('/batches', [ImportController::class, 'batches']);
            });

            // User Management
            Route::apiResource('users', UserController::class);
            Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword']);

            // Activity Logs (Read-only)
            Route::get('/activity-logs', [ActivityLogController::class, 'index']);

            // System Settings (Update)
            Route::put('/settings', [SettingController::class, 'update']);

            // Attendance Approvals & Correction (FR-ABS-09, FR-ABS-10)
            Route::post('/attendance/teachers/{id}/approve-leave', [TeacherAttendanceController::class, 'approveLeave']);
            Route::put('/attendance/teachers/{id}/correct', [TeacherAttendanceController::class, 'correct']);

            // Announcements Management (Admin)
            Route::post('/announcements', [AnnouncementController::class, 'store']);
            Route::put('/announcements/{id}', [AnnouncementController::class, 'update']);
            Route::delete('/announcements/{id}', [AnnouncementController::class, 'destroy']);

            // Finance Management (FR-KEU-01 s/d FR-KEU-06, FR-KEU-08)
            Route::apiResource('fee-types', FeeTypeController::class);
            Route::post('/invoices', [InvoiceController::class, 'store']);
            Route::post('/invoices/generate-batch', [InvoiceController::class, 'generateBatch']);
            Route::put('/invoices/{id}', [InvoiceController::class, 'update']);
            Route::delete('/invoices/{id}', [InvoiceController::class, 'destroy']);
            Route::post('/payments', [PaymentController::class, 'store']);
            Route::post('/payments/{id}/cancel', [PaymentController::class, 'cancel']);
            Route::get('/finance-transactions', [FinanceTransactionController::class, 'index']);
            Route::post('/finance-transactions', [FinanceTransactionController::class, 'store']);
            Route::get('/reports/finance', [FinanceTransactionController::class, 'report']);

            // Sarpras & Assets (FR-SAR-01 s/d FR-SAR-06)
            Route::apiResource('assets', AssetController::class);
            Route::post('/assets/{id}/maintenance', [AssetController::class, 'storeMaintenance']);
            Route::post('/assets/{id}/borrow', [AssetController::class, 'borrow']);
            Route::post('/assets/loans/{id}/return', [AssetController::class, 'returnLoan']);

            // Executive Dashboard Summary (Kepala Sekolah & Admin)
            Route::get('/dashboard/summary', [DashboardController::class, 'summary']);

            // Backup & Restore System (FR-BCK-01 s/d FR-BCK-05)
            Route::prefix('backups')->group(function () {
                Route::get('/', [BackupController::class, 'index']);
                Route::post('/', [BackupController::class, 'store']);
                Route::get('/{id}/download', [BackupController::class, 'download']);
                Route::post('/{id}/restore', [BackupController::class, 'restore']);
                Route::delete('/{id}', [BackupController::class, 'destroy']);
            });

            // CMS Web Sekolah (Fase 2: Galeri, Prestasi, Warta/Artikel)
            Route::prefix('cms')->group(function () {
                Route::get('/galleries', [CmsController::class, 'indexGalleries']);
                Route::post('/galleries', [CmsController::class, 'storeGallery']);
                Route::delete('/galleries/{id}', [CmsController::class, 'destroyGallery']);

                Route::get('/achievements', [CmsController::class, 'indexAchievements']);
                Route::post('/achievements', [CmsController::class, 'storeAchievement']);
                Route::delete('/achievements/{id}', [CmsController::class, 'destroyAchievement']);

                Route::get('/articles', [CmsController::class, 'indexArticles']);
                Route::post('/articles', [CmsController::class, 'storeArticle']);
                Route::delete('/articles/{id}', [CmsController::class, 'destroyArticle']);

                Route::get('/teachers', [CmsController::class, 'indexTeachers']);
                Route::post('/teachers/{id}', [CmsController::class, 'updateTeacher']);
                Route::delete('/teachers/{id}/photo', [CmsController::class, 'deleteTeacherPhoto']);
            });

            // PPDB Online (Penerimaan Peserta Didik Baru Terpadu KB, TK, SD)
            Route::prefix('ppdb')->group(function () {
                Route::get('/', [PpdbController::class, 'index']);
                Route::get('/export', [PpdbController::class, 'exportExcel']);
                Route::get('/{id}', [PpdbController::class, 'show']);
                Route::put('/{id}/status', [PpdbController::class, 'updateStatus']);
                Route::delete('/{id}', [PpdbController::class, 'destroy']);
            });
        });

        // Rapor Siswa Multi-Format & Validasi (Wali Kelas, Kepala Sekolah & Admin)
        Route::middleware('role:kepala_sekolah,staf_admin,guru')->prefix('report-cards')->group(function () {
            Route::get('/', [ReportCardController::class, 'index']);
            Route::get('/default-subjects', [ReportCardController::class, 'defaultSubjects']);
            Route::get('/default-templates', [ReportCardController::class, 'defaultTemplates']);
            Route::post('/initialize-class', [ReportCardController::class, 'initializeClass']);
            Route::post('/student', [ReportCardController::class, 'getOrCreateForStudent']);
            Route::get('/class-grade-book', [ReportCardController::class, 'getClassGradeBook']);
            Route::post('/class-grade-book', [ReportCardController::class, 'saveClassGradeBook']);
            Route::get('/export-excel', [ReportCardController::class, 'exportExcel']);
            Route::post('/import-excel', [ReportCardController::class, 'importExcel']);
            Route::post('/submit-class', [ReportCardController::class, 'submitClassForValidation']);
            Route::post('/validate-class', [ReportCardController::class, 'validateClass']);
            Route::post('/sync-class-attendance', [ReportCardController::class, 'syncClassAttendance']);
            Route::get('/{id}', [ReportCardController::class, 'show']);
            Route::put('/{id}', [ReportCardController::class, 'update']);
            Route::post('/{id}/sync-attendance', [ReportCardController::class, 'syncAttendance']);
            Route::post('/{id}/submit', [ReportCardController::class, 'submitForValidation']);
            Route::post('/{id}/validate', [ReportCardController::class, 'validateReportCard']);
            Route::post('/{id}/request-revision', [ReportCardController::class, 'requestRevision']);
            Route::get('/student/{studentId}/history', [ReportCardController::class, 'studentHistory']);
        });
    });
});

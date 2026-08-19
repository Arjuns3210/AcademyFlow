<?php

use App\Http\Controllers\Institute\AttendanceController;
use App\Http\Controllers\Institute\BatchController;
use App\Http\Controllers\Institute\CourseController;
use App\Http\Controllers\Institute\FeatureLockedController;
use App\Http\Controllers\Institute\FeeController;
use App\Http\Controllers\Institute\ReminderController;
use App\Http\Controllers\Institute\ReportController;
use App\Http\Controllers\Institute\RoleController;
use App\Http\Controllers\Institute\StaffController;
use App\Http\Controllers\Institute\StudentController;
use App\Http\Controllers\Institute\TeacherController;
use App\Http\Controllers\Institute\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->prefix('institute')
    ->name('institute.')
    ->group(function () {
        Route::get('feature-locked/{moduleKey}', [FeatureLockedController::class, 'show'])->name('feature-locked');

        // Same "settings"-permission gate as Roles/Staff below — not one of
        // the seeded tenant modules, so gated here rather than via canModule.
        Route::middleware(['permission:settings,view', 'module:whatsapp_reminder'])->group(function () {
            Route::get('whatsapp', [WhatsAppController::class, 'index'])->name('whatsapp.index');
        });

        // Combined "Send Reminder" module — its two sections (Fee Dues /
        // Absent Students) are independently gated inside the controller by
        // fees,view / attendance,view (same per-section split precedent as
        // the dashboard's stat cards), since a role might have one but not
        // the other. module:whatsapp_reminder alone at the route level.
        Route::middleware('module:whatsapp_reminder')->group(function () {
            Route::get('reminders', [ReminderController::class, 'index'])->name('reminders.index');
        });

        // Manual wa.me quick-send is free on every plan (no Meta API cost) —
        // deliberately outside the module:whatsapp_reminder gate above, even
        // though it currently only lives on a page that gate still guards.
        Route::middleware('permission:fees,view')->group(function () {
            Route::post('reminders/{studentFee}/whatsapp-manual-log', [ReminderController::class, 'logManualWhatsApp'])->name('reminders.whatsapp-manual-log');
        });

        Route::middleware('permission:attendance,view')->group(function () {
            Route::post('reminders/attendance/{student}/whatsapp-manual-log', [ReminderController::class, 'logManualWhatsAppAttendance'])->name('reminders.attendance.whatsapp-manual-log');
        });

        Route::middleware('permission:settings,view')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
        });

        Route::middleware('permission:settings,add')->group(function () {
            Route::get('roles/create', [RoleController::class, 'create'])->name('roles.create');
            Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
        });

        Route::middleware('permission:settings,edit')->group(function () {
            Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
            Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');
        });

        Route::middleware('permission:settings,delete')->group(function () {
            Route::delete('roles/{role}', [RoleController::class, 'destroy'])->name('roles.destroy');
        });

        // Staff isn't one of our seeded modules — gated under "settings" for
        // now, same as Roles. Revisit if Staff ever warrants its own module row.
        Route::middleware('permission:settings,view')->group(function () {
            Route::get('staff', [StaffController::class, 'index'])->name('staff.index');
        });

        Route::middleware('permission:settings,add')->group(function () {
            Route::get('staff/create', [StaffController::class, 'create'])->name('staff.create');
            Route::post('staff', [StaffController::class, 'store'])->name('staff.store');
        });

        Route::middleware('permission:settings,edit')->group(function () {
            Route::get('staff/{user}/edit', [StaffController::class, 'edit'])->name('staff.edit');
            Route::put('staff/{user}', [StaffController::class, 'update'])->name('staff.update');
            Route::post('staff/{user}/reset-password', [StaffController::class, 'resetPassword'])->name('staff.reset-password');
        });

        Route::middleware('permission:settings,status')->group(function () {
            Route::patch('staff/{user}/toggle-status', [StaffController::class, 'toggleStatus'])->name('staff.toggle-status');
        });

        Route::middleware('permission:students,view')->group(function () {
            Route::get('students', [StudentController::class, 'index'])->name('students.index');
        });

        Route::middleware('permission:students,add')->group(function () {
            Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
            Route::post('students', [StudentController::class, 'store'])->name('students.store');
        });

        // Must be registered after students/create — as a wildcard segment it
        // would otherwise shadow that static route and swallow "create" as
        // the {student} parameter.
        Route::middleware('permission:students,view')->group(function () {
            Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
            Route::get('students/course-batches/{course}', [StudentController::class, 'batchesForCourse'])->name('students.course-batches');
        });

        Route::middleware('permission:students,edit')->group(function () {
            Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
            Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
        });

        Route::middleware('permission:students,delete')->group(function () {
            Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
        });

        Route::middleware('permission:students,status')->group(function () {
            Route::patch('students/{student}/status', [StudentController::class, 'updateStatus'])->name('students.update-status');
        });

        Route::middleware('permission:courses,view')->group(function () {
            Route::get('courses', [CourseController::class, 'index'])->name('courses.index');
        });

        Route::middleware('permission:courses,add')->group(function () {
            Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
            Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        });

        Route::middleware('permission:courses,edit')->group(function () {
            Route::get('courses/{course}/edit', [CourseController::class, 'edit'])->name('courses.edit');
            Route::put('courses/{course}', [CourseController::class, 'update'])->name('courses.update');
        });

        Route::middleware('permission:courses,delete')->group(function () {
            Route::delete('courses/{course}', [CourseController::class, 'destroy'])->name('courses.destroy');
        });

        Route::middleware('permission:courses,status')->group(function () {
            Route::patch('courses/{course}/status', [CourseController::class, 'updateStatus'])->name('courses.update-status');
        });

        Route::middleware('permission:teachers,view')->group(function () {
            Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
        });

        Route::middleware('permission:teachers,add')->group(function () {
            Route::get('teachers/create', [TeacherController::class, 'create'])->name('teachers.create');
            Route::post('teachers', [TeacherController::class, 'store'])->name('teachers.store');
        });

        Route::middleware('permission:teachers,edit')->group(function () {
            Route::get('teachers/{teacher}/edit', [TeacherController::class, 'edit'])->name('teachers.edit');
            Route::put('teachers/{teacher}', [TeacherController::class, 'update'])->name('teachers.update');
        });

        Route::middleware('permission:teachers,delete')->group(function () {
            Route::delete('teachers/{teacher}', [TeacherController::class, 'destroy'])->name('teachers.destroy');
        });

        Route::middleware('permission:teachers,status')->group(function () {
            Route::patch('teachers/{teacher}/status', [TeacherController::class, 'updateStatus'])->name('teachers.update-status');
        });

        Route::middleware('permission:batches,view')->group(function () {
            Route::get('batches', [BatchController::class, 'index'])->name('batches.index');
        });

        Route::middleware('permission:batches,add')->group(function () {
            Route::get('batches/create', [BatchController::class, 'create'])->name('batches.create');
            Route::post('batches', [BatchController::class, 'store'])->name('batches.store');
        });

        Route::middleware('permission:batches,edit')->group(function () {
            Route::get('batches/{batch}/edit', [BatchController::class, 'edit'])->name('batches.edit');
            Route::put('batches/{batch}', [BatchController::class, 'update'])->name('batches.update');
        });

        Route::middleware('permission:batches,delete')->group(function () {
            Route::delete('batches/{batch}', [BatchController::class, 'destroy'])->name('batches.destroy');
        });

        Route::middleware('permission:batches,status')->group(function () {
            Route::patch('batches/{batch}/status', [BatchController::class, 'updateStatus'])->name('batches.update-status');
        });

        Route::middleware('permission:fees,view')->group(function () {
            Route::get('fees', [FeeController::class, 'index'])->name('fees.index');
            Route::get('fees/{studentFee}', [FeeController::class, 'show'])->name('fees.show');
            Route::get('fees/transactions/{transaction}/receipt', [FeeController::class, 'receipt'])->name('fees.transactions.receipt');
        });

        Route::middleware('permission:fees,add')->group(function () {
            Route::post('fees/{studentFee}/payments', [FeeController::class, 'storePayment'])->name('fees.payments.store');
        });

        Route::middleware('permission:fees,edit')->group(function () {
            Route::patch('fees/{studentFee}/discount', [FeeController::class, 'updateDiscount'])->name('fees.discount.update');
            Route::patch('fees/{studentFee}/installments', [FeeController::class, 'updateInstallments'])->name('fees.installments.update');
        });

        // fees,delete is repurposed as "void a transaction" rather than a
        // literal delete — same semantic mapping as soft-delete elsewhere,
        // since a raw delete makes no sense for a financial record.
        Route::middleware('permission:fees,delete')->group(function () {
            Route::patch('fees/transactions/{transaction}/void', [FeeController::class, 'voidTransaction'])->name('fees.transactions.void');
        });

        Route::middleware(['permission:fees,view', 'module:whatsapp_reminder'])->group(function () {
            Route::post('fees/{studentFee}/send-reminder', [FeeController::class, 'sendReminder'])->name('fees.send-reminder');
            Route::post('fees/send-bulk-reminders', [FeeController::class, 'sendBulkReminders'])->name('fees.send-bulk-reminders');
        });

        // attendance,view is the baseline route gate — marking today vs
        // correcting a past date (attendance,add vs attendance,edit) is
        // decided per-submission by StoreAttendanceRequest::authorize(),
        // since the required action depends on the submitted date, not
        // just the route.
        Route::middleware('permission:attendance,view')->group(function () {
            Route::get('attendance', [AttendanceController::class, 'index'])->name('attendance.index');
            Route::post('attendance', [AttendanceController::class, 'store'])->name('attendance.store');
            Route::get('attendance/export', [AttendanceController::class, 'exportDaily'])->name('attendance.export');
            Route::get('attendance/monthly', [AttendanceController::class, 'monthly'])->name('attendance.monthly');
            Route::get('attendance/monthly/export', [AttendanceController::class, 'exportMonthly'])->name('attendance.monthly.export');
        });

        Route::middleware(['permission:attendance,view', 'module:whatsapp_reminder'])->group(function () {
            Route::post('attendance/{student}/send-reminder', [AttendanceController::class, 'sendReminder'])->name('attendance.send-reminder');
            Route::post('attendance/send-bulk-reminders', [AttendanceController::class, 'sendBulkReminders'])->name('attendance.send-bulk-reminders');
        });

        // Reports is a read-only aggregation layer over Students/Fees/
        // Attendance — a single reports,view permission covers the whole
        // module, no add/edit/delete/status concept applies.
        Route::middleware('permission:reports,view')->group(function () {
            Route::get('reports', [ReportController::class, 'index'])->name('reports.index');

            Route::get('reports/students', [ReportController::class, 'students'])->name('reports.students');
            Route::get('reports/students/export', [ReportController::class, 'exportStudents'])->name('reports.students.export');
            Route::get('reports/students/export-pdf', [ReportController::class, 'exportStudentsPdf'])->name('reports.students.export-pdf');

            Route::get('reports/attendance', [ReportController::class, 'attendance'])->name('reports.attendance');
            Route::get('reports/attendance/export', [ReportController::class, 'exportAttendance'])->name('reports.attendance.export');
            Route::get('reports/attendance/export-pdf', [ReportController::class, 'exportAttendancePdf'])->name('reports.attendance.export-pdf');

            Route::get('reports/fees', [ReportController::class, 'fees'])->name('reports.fees');
            Route::get('reports/fees/export', [ReportController::class, 'exportFees'])->name('reports.fees.export');
            Route::get('reports/fees/export-pdf', [ReportController::class, 'exportFeesPdf'])->name('reports.fees.export-pdf');

            Route::get('reports/collections', [ReportController::class, 'collections'])->name('reports.collections');
            Route::get('reports/collections/export', [ReportController::class, 'exportCollections'])->name('reports.collections.export');
            Route::get('reports/collections/export-pdf', [ReportController::class, 'exportCollectionsPdf'])->name('reports.collections.export-pdf');
        });
    });

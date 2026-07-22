<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Plan;
use App\Models\Student;
use App\Models\User;
use App\Services\InstituteProvisioningService;
use Closure;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Confirms query count stays flat as row count grows, on the four pages
 * named in Sprint 8C: student list, fee report, attendance monthly view,
 * and the Super Admin subscriptions list. Found one real bug this way:
 * StudentFee::paidAmount() called $this->transactions() (the relation
 * method, always a fresh query) instead of using the eager-loaded
 * $this->transactions collection, so the Fee Report's with('transactions')
 * was silently not helping at all — one query per row regardless. Fixed in
 * the model; this test is what would catch it coming back.
 */
class NPlusOneQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_student_list_query_count_is_flat(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'N+1 Course', 'duration' => '1 month', 'fee' => 500]);
        $batch = Batch::create(['tenant_id' => $admin->tenant_id, 'course_id' => $course->id, 'name' => 'N+1 Batch', 'time_slot' => '5 PM']);

        [$smallCount, $largeCount] = $this->compareQueryCounts(
            fn () => $this->makeStudents($admin->tenant_id, $course->id, $batch->id, 3, 'SmallBatch'),
            fn () => $this->makeStudents($admin->tenant_id, $course->id, $batch->id, 15, 'LargeBatch'),
            fn () => $this->actingAs($admin)->get(route('institute.students.index'))
        );

        $this->assertSame($smallCount, $largeCount, "student list: {$smallCount} queries at 3 rows vs {$largeCount} at 18 rows");
    }

    public function test_fee_report_query_count_is_flat(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'N+1 Course', 'duration' => '1 month', 'fee' => 500]);

        [$smallCount, $largeCount] = $this->compareQueryCounts(
            fn () => $this->makeStudentsWithFees($admin->tenant_id, $course->id, 3, 'SmallFee'),
            fn () => $this->makeStudentsWithFees($admin->tenant_id, $course->id, 15, 'LargeFee'),
            fn () => $this->actingAs($admin)->get(route('institute.reports.fees'))
        );

        $this->assertSame($smallCount, $largeCount, "fee report: {$smallCount} queries at 3 rows vs {$largeCount} at 18 rows");
    }

    public function test_attendance_monthly_query_count_is_flat(): void
    {
        $admin = $this->tenantAdmin();
        $course = Course::create(['tenant_id' => $admin->tenant_id, 'name' => 'N+1 Course', 'duration' => '1 month', 'fee' => 500]);

        [$smallCount, $largeCount] = $this->compareQueryCounts(
            fn () => $this->makeStudentsWithAttendance($admin->tenant_id, $course->id, 3, 'SmallAtt'),
            fn () => $this->makeStudentsWithAttendance($admin->tenant_id, $course->id, 15, 'LargeAtt'),
            fn () => $this->actingAs($admin)->get(route('institute.attendance.monthly'))
        );

        $this->assertSame($smallCount, $largeCount, "attendance monthly: {$smallCount} queries at 3 rows vs {$largeCount} at 18 rows");
    }

    public function test_subscriptions_list_query_count_is_flat(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $plan = Plan::first();

        [$smallCount, $largeCount] = $this->compareQueryCounts(
            fn () => $this->makeInstitutesWithSubscriptions(3, 'SmallInst', $plan),
            fn () => $this->makeInstitutesWithSubscriptions(15, 'LargeInst', $plan),
            fn () => $this->actingAs($superAdmin)->get(route('super-admin.subscriptions.index'))
        );

        $this->assertSame($smallCount, $largeCount, "subscriptions list: {$smallCount} queries at 3+1 rows vs {$largeCount} at 18+1 rows");
    }

    /**
     * @return array{0: int, 1: int} [queryCountAtSmallN, queryCountAtLargeN]
     */
    private function compareQueryCounts(Closure $seedSmall, Closure $seedLarge, Closure $hitPage): array
    {
        $seedSmall();

        // Warmup call, discarded — the first hit in a test method costs
        // extra queries (permission/role lookups, etc.) that don't repeat
        // on a second hit within the same method. Without this, comparing
        // "1st call" vs "2nd call" confounds that warmup cost with the
        // thing actually being measured (does query count scale with N).
        $hitPage();

        DB::flushQueryLog();
        DB::enableQueryLog();
        $hitPage()->assertOk();
        $smallCount = count(DB::getQueryLog());
        DB::flushQueryLog();

        $seedLarge();
        DB::flushQueryLog();
        $hitPage()->assertOk();
        $largeCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        return [$smallCount, $largeCount];
    }

    private function makeStudents(int $tenantId, int $courseId, int $batchId, int $count, string $prefix): void
    {
        for ($i = 1; $i <= $count; $i++) {
            Student::create([
                'tenant_id' => $tenantId, 'name' => "{$prefix} Student {$i}",
                'parent_mobile' => '90000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'course_id' => $courseId, 'batch_id' => $batchId,
            ]);
        }
    }

    private function makeStudentsWithFees(int $tenantId, int $courseId, int $count, string $prefix): void
    {
        $admin = $this->tenantAdmin();

        for ($i = 1; $i <= $count; $i++) {
            $student = Student::create([
                'tenant_id' => $tenantId, 'name' => "{$prefix} Student {$i}",
                'parent_mobile' => '91000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'course_id' => $courseId,
            ]);
            FeeTransaction::create([
                'tenant_id' => $tenantId, 'student_fee_id' => $student->fee->id,
                'amount' => 100, 'payment_mode' => 'cash', 'collected_by' => $admin->id,
            ]);
        }
    }

    private function makeStudentsWithAttendance(int $tenantId, int $courseId, int $count, string $prefix): void
    {
        $admin = $this->tenantAdmin();

        for ($i = 1; $i <= $count; $i++) {
            $student = Student::create([
                'tenant_id' => $tenantId, 'name' => "{$prefix} Student {$i}",
                'parent_mobile' => '92000'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'course_id' => $courseId, 'status' => 'active',
            ]);
            Attendance::mark($tenantId, $student->id, null, now()->toDateString(), 'present', $admin->id);
        }
    }

    private function makeInstitutesWithSubscriptions(int $count, string $prefix, Plan $plan): void
    {
        $service = app(InstituteProvisioningService::class);
        $type = InstituteType::firstOrFail();

        for ($i = 1; $i <= $count; $i++) {
            $institute = Institute::create(['institute_type_id' => $type->id, 'name' => "{$prefix} {$i}", 'status' => 'active']);
            $subscription = $service->startTrialSubscription($institute);
            $subscription->update(['status' => 'active', 'plan_id' => $plan->id, 'amount_paid' => $plan->price]);
        }
    }

    private function tenantAdmin(): User
    {
        return User::where('email', 'admin@brightcoaching.com')->firstOrFail();
    }
}

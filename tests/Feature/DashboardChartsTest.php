<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class DashboardChartsTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_dashboard_charts_are_tenant_scoped_and_exclude_voided_transactions(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;

        $course = Course::create(['tenant_id' => $tenantId, 'name' => 'Chart Course', 'duration' => '1 month', 'fee' => 1000]);
        $student = Student::create([
            'tenant_id' => $tenantId,
            'name' => 'Chart Student',
            'parent_mobile' => '9990000001',
            'course_id' => $course->id,
            'admission_date' => now()->toDateString(),
        ]);

        FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $student->fee->id,
            'amount' => 54321,
            'payment_mode' => 'cash',
            'collected_by' => $admin->id,
            'payment_date' => now()->toDateString(),
        ]);

        $voided = FeeTransaction::create([
            'tenant_id' => $tenantId,
            'student_fee_id' => $student->fee->id,
            'amount' => 11111,
            'payment_mode' => 'cash',
            'collected_by' => $admin->id,
            'payment_date' => now()->toDateString(),
        ]);
        $voided->void($admin, 'test void');

        [$instituteB, $adminB] = $this->createSecondInstituteWithAdmin();
        $courseB = Course::create(['tenant_id' => $instituteB->id, 'name' => 'B Course', 'duration' => '1 month', 'fee' => 1000]);
        $studentB = Student::create([
            'tenant_id' => $instituteB->id,
            'name' => 'Institute B Student',
            'parent_mobile' => '8880000001',
            'course_id' => $courseB->id,
            'admission_date' => now()->toDateString(),
        ]);
        FeeTransaction::create([
            'tenant_id' => $instituteB->id,
            'student_fee_id' => $studentB->fee->id,
            'amount' => 123456,
            'payment_mode' => 'cash',
            'collected_by' => $adminB->id,
            'payment_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('id="monthlyCollectionChart"', false);
        $response->assertSee('id="admissionTrendChart"', false);
        $response->assertDontSee('coming in Sprint 6');

        // This tenant's non-voided amount must render (in the chart data);
        // the voided amount and the other tenant's amount must not appear
        // anywhere on the page.
        $response->assertSee('54321');
        $response->assertDontSee('11111');
        $response->assertDontSee('123456');
    }

    public function test_dashboard_charts_are_hidden_without_the_underlying_module_permission(): void
    {
        $admin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $tenantId = $admin->tenant_id;
        $viewer = $this->createViewOnlyUser($tenantId, 'attendance');

        $response = $this->actingAs($viewer)->get(route('dashboard'));

        $response->assertOk();
        $response->assertDontSee('id="monthlyCollectionChart"', false);
        $response->assertDontSee('id="admissionTrendChart"', false);
    }
}

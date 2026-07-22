<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Module;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One continuous narrative through the whole product, end to end, rather
 * than each sprint's own isolated slice — the kind of gap that "everything
 * passes sprint by sprint" wouldn't surface, since no single sprint's tests
 * carry a subscription through creation, trial expiry, and reactivation
 * while also exercising a real limited-permission staff member along the
 * way.
 */
class FullLifecycleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_full_institute_lifecycle_from_creation_through_trial_expiry_to_reactivation(): void
    {
        $superAdmin = User::where('email', 'admin@academyflow.in')->firstOrFail();
        $instituteType = InstituteType::firstOrFail();

        // Modules this institute needs enabled — "settings" gates role/staff
        // creation (Roles and Staff aren't their own seeded modules), the
        // rest are what the lifecycle below actually exercises.
        $studentsModule = Module::where('slug', 'students')->firstOrFail();
        $feesModule = Module::where('slug', 'fees')->firstOrFail();
        $attendanceModule = Module::where('slug', 'attendance')->firstOrFail();
        $reportsModule = Module::where('slug', 'reports')->firstOrFail();
        $coursesModule = Module::where('slug', 'courses')->firstOrFail();
        $settingsModule = Module::where('slug', 'settings')->firstOrFail();

        // 1. Super Admin creates a new institute through the real HTTP flow.
        $this->actingAs($superAdmin)->post(route('super-admin.institutes.store'), [
            'name' => 'Lifecycle Test Institute',
            'institute_type_id' => $instituteType->id,
            'admin_name' => 'Lifecycle Admin',
            'admin_email' => 'lifecycle-admin@example.com',
            'admin_password' => 'password123',
            'admin_password_confirmation' => 'password123',
            'modules' => [
                $studentsModule->id, $feesModule->id, $attendanceModule->id,
                $reportsModule->id, $coursesModule->id, $settingsModule->id,
            ],
        ])->assertRedirect(route('super-admin.institutes.index'));

        $institute = Institute::where('name', 'Lifecycle Test Institute')->firstOrFail();
        $instituteAdmin = User::where('email', 'lifecycle-admin@example.com')->firstOrFail();
        $subscription = $institute->subscription;

        $this->assertSame('trial', $subscription->status);

        // 2. Institute Admin creates a role with LIMITED permissions, and a staff member on it.

        $this->actingAs($instituteAdmin)->post(route('institute.roles.store'), [
            'name' => 'Front Desk',
            'permissions' => [
                $studentsModule->id => ['view' => true, 'add' => true],
                $feesModule->id => ['view' => true, 'add' => true],
                $attendanceModule->id => ['view' => true, 'add' => true],
                $reportsModule->id => ['view' => true],
                // Deliberately no courses permission at all, to prove the
                // limited role really is limited, not just missing delete.
            ],
        ])->assertRedirect(route('institute.roles.index'));

        $limitedRole = Role::where('tenant_id', $institute->id)->where('name', 'Front Desk')->firstOrFail();

        $this->actingAs($instituteAdmin)->post(route('institute.staff.store'), [
            'name' => 'Front Desk Staff',
            'email' => 'frontdesk@example.com',
            'role_id' => $limitedRole->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertRedirect(route('institute.staff.index'));

        $staff = User::where('email', 'frontdesk@example.com')->firstOrFail();

        // Institute Admin creates the course the limited staff will use —
        // staff has no courses permission at all, matching a front-desk
        // role that admits students but doesn't manage the catalog.
        $course = Course::create(['tenant_id' => $institute->id, 'name' => 'Lifecycle Course', 'duration' => '1 month', 'fee' => 1000]);
        $this->actingAs($staff)->get(route('institute.courses.index'))->assertForbidden();

        // 3. Staff (limited permissions) admits a student.
        $this->actingAs($staff)->post(route('institute.students.store'), [
            'name' => 'Lifecycle Student',
            'parent_mobile' => '9876543210',
            'course_id' => $course->id,
        ])->assertRedirect();

        $student = Student::where('tenant_id', $institute->id)->where('name', 'Lifecycle Student')->firstOrFail();

        // 4. Fee was auto-assigned on admission (Sprint 4's StudentObserver); staff collects a partial payment.
        $this->assertNotNull($student->fee);
        $this->assertSame('1000.00', $student->fee->total_fee);

        $this->actingAs($staff)->post(route('institute.fees.payments.store', $student->fee), [
            'amount' => 400,
            'payment_mode' => 'cash',
        ])->assertRedirect();

        $this->assertSame(400.0, $student->fee->fresh()->paid_amount);
        $this->assertSame('partial', $student->fee->fresh()->status);

        // Staff cannot edit or delete the student — the limited role only granted view+add.
        $this->actingAs($staff)->delete(route('institute.students.destroy', $student))->assertForbidden();

        // 5. Staff marks attendance.
        $this->actingAs($staff)->post(route('institute.attendance.store'), [
            'attendance_date' => now()->toDateString(),
            'statuses' => [$student->id => 'present'],
        ])->assertRedirect();

        $this->assertDatabaseHas('attendance', ['student_id' => $student->id, 'status' => 'present']);

        // 6. Staff generates a report.
        $this->actingAs($staff)->get(route('institute.reports.students'))
            ->assertOk()
            ->assertSee('Lifecycle Student');

        // 7. Trial expires.
        $subscription->update(['trial_ends_at' => now()->subDay()]);
        $this->artisan('subscriptions:expire');
        $this->assertSame('expired', $subscription->fresh()->status);

        // 8. Login is blocked for this institute's users — both the admin and the limited staff.
        // The test session is still authenticated as $staff from step 6 —
        // the login route sits behind 'guest' middleware, so it would
        // redirect away before ever running the subscription check unless
        // logged out first.
        $this->post(route('logout'));
        $this->assertGuest();

        $adminLogin = $this->post(route('login'), ['email' => $instituteAdmin->email, 'password' => 'password123']);
        $adminLogin->assertSessionHasErrors(['email' => 'Your subscription has expired. Please contact us to renew.']);
        $this->assertGuest();

        $staffLogin = $this->post(route('login'), ['email' => $staff->email, 'password' => 'password123']);
        $staffLogin->assertSessionHasErrors('email');
        $this->assertGuest();

        // 9. Super Admin activates a subscription.
        $plan = Plan::where('name', 'Starter')->firstOrFail();
        $this->actingAs($superAdmin)->put(route('super-admin.subscriptions.update', $subscription), [
            'plan_id' => $plan->id,
            'current_period_start' => now()->toDateString(),
            'current_period_end' => now()->addMonth()->toDateString(),
        ])->assertRedirect(route('super-admin.subscriptions.index'));

        $this->assertSame('active', $subscription->fresh()->status);
        $this->assertSame($plan->id, $subscription->fresh()->plan_id);

        // 10. Login is restored.
        $this->post(route('logout'));
        $this->post(route('login'), ['email' => $instituteAdmin->email, 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($instituteAdmin);
    }
}

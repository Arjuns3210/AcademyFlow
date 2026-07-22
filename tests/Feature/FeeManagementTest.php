<?php

namespace Tests\Feature;

use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesSecondInstitute;
use Tests\Concerns\CreatesViewOnlyUser;
use Tests\TestCase;

class FeeManagementTest extends TestCase
{
    use CreatesSecondInstitute, CreatesViewOnlyUser, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);
    }

    public function test_payment_cannot_exceed_pending_amount(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentFee = $this->createStudentWithFee($instituteAdmin->tenant_id, 1000)->fee;

        $response = $this->actingAs($instituteAdmin)->post(route('institute.fees.payments.store', $studentFee), [
            'amount' => 1500,
            'payment_mode' => 'cash',
        ]);

        $response->assertSessionHasErrors('amount');
        $this->assertSame(0, $studentFee->fresh()->transactions()->count());
    }

    public function test_voiding_transaction_excludes_it_from_paid_and_pending_without_deleting_the_row(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentFee = $this->createStudentWithFee($instituteAdmin->tenant_id, 1000)->fee;

        $transaction = FeeTransaction::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => 400,
            'payment_mode' => 'cash',
            'collected_by' => $instituteAdmin->id,
        ]);

        $this->assertEquals(400.0, $studentFee->fresh()->paid_amount);

        $response = $this->actingAs($instituteAdmin)
            ->patch(route('institute.fees.transactions.void', $transaction), ['void_reason' => 'Refunded in cash']);

        $response->assertRedirect(route('institute.fees.show', $studentFee));

        // Still exists — voided, not deleted.
        $this->assertNotNull(FeeTransaction::find($transaction->id));
        $this->assertTrue($transaction->fresh()->isVoided());
        $this->assertEquals(0.0, $studentFee->fresh()->paid_amount);
        $this->assertEquals(1000.0, $studentFee->fresh()->pending_amount);

        // Voiding an already-voided transaction is rejected, not silently repeated.
        $this->actingAs($instituteAdmin)
            ->patch(route('institute.fees.transactions.void', $transaction), ['void_reason' => 'again'])
            ->assertStatus(422);
    }

    public function test_receipt_number_retries_on_simulated_collision_instead_of_failing(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentFee = $this->createStudentWithFee($instituteAdmin->tenant_id, 1000)->fee;

        $first = FeeTransaction::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => 100,
            'payment_mode' => 'cash',
            'collected_by' => $instituteAdmin->id,
        ]);

        // Force a second transaction to attempt the exact same receipt
        // number $first already holds — simulating two concurrent requests
        // that both computed the same good-faith guess before either
        // committed. Pre-setting receipt_number bypasses the creating()
        // hook's own auto-generation (which only fires when the attribute
        // is empty), so this genuinely collides on the first insert
        // attempt, exercising performInsert()'s retry-on-collision path
        // rather than the count-based guess (which would naturally avoid
        // colliding on its own and wouldn't prove anything).
        $second = FeeTransaction::make([
            'tenant_id' => $instituteAdmin->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => 200,
            'payment_mode' => 'cash',
            'collected_by' => $instituteAdmin->id,
            'receipt_number' => $first->receipt_number,
        ]);
        $second->save();

        $this->assertNotSame($first->receipt_number, $second->receipt_number);
        $this->assertMatchesRegularExpression('/^[A-Z]+-RCT-\d{6}$/', $second->receipt_number);
        $this->assertSame(2, FeeTransaction::where('student_fee_id', $studentFee->id)->count());
    }

    public function test_user_with_only_fees_view_permission_gets_403_on_collect_payment_and_void(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentFee = $this->createStudentWithFee($instituteAdmin->tenant_id, 1000)->fee;

        $transaction = FeeTransaction::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => 100,
            'payment_mode' => 'cash',
            'collected_by' => $instituteAdmin->id,
        ]);

        $viewer = $this->createViewOnlyUser($instituteAdmin->tenant_id, 'fees');

        $this->actingAs($viewer)
            ->post(route('institute.fees.payments.store', $studentFee), ['amount' => 100, 'payment_mode' => 'cash'])
            ->assertForbidden();

        $this->actingAs($viewer)
            ->patch(route('institute.fees.transactions.void', $transaction), ['void_reason' => 'test'])
            ->assertForbidden();

        // fees,view alone is still enough to see the detail page — this
        // role was never granted any Students permission, confirming Fees
        // and Students permissions really are independent.
        $this->actingAs($viewer)->get(route('institute.fees.show', $studentFee))->assertOk();
    }

    public function test_tenant_isolation_on_fee_routes_and_receipt_pdf(): void
    {
        $instituteAdmin = User::where('email', 'admin@brightcoaching.com')->firstOrFail();
        $studentFee = $this->createStudentWithFee($instituteAdmin->tenant_id, 1000)->fee;

        $transaction = FeeTransaction::create([
            'tenant_id' => $instituteAdmin->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => 100,
            'payment_mode' => 'cash',
            'collected_by' => $instituteAdmin->id,
        ]);

        [, $adminB] = $this->createSecondInstituteWithAdmin();

        $this->actingAs($adminB)->get(route('institute.fees.show', $studentFee))->assertNotFound();
        $this->actingAs($adminB)->get(route('institute.fees.transactions.receipt', $transaction))->assertNotFound();
        $this->actingAs($adminB)
            ->post(route('institute.fees.payments.store', $studentFee), ['amount' => 50, 'payment_mode' => 'cash'])
            ->assertNotFound();
        $this->actingAs($adminB)
            ->patch(route('institute.fees.transactions.void', $transaction), ['void_reason' => 'x'])
            ->assertNotFound();

        // Institute A's data must survive all of Institute B's failed attempts.
        $this->assertNotNull(StudentFee::withoutGlobalScopes()->find($studentFee->id));
        $this->assertFalse($transaction->fresh()->isVoided());
    }

    private function createStudentWithFee(int $tenantId, float $courseFee): Student
    {
        $course = Course::create([
            'tenant_id' => $tenantId,
            'name' => 'Fee Test Course',
            'duration' => '1 month',
            'fee' => $courseFee,
        ]);

        return Student::create([
            'tenant_id' => $tenantId,
            'name' => 'Fee Test Student',
            'parent_mobile' => '9990000001',
            'course_id' => $course->id,
        ]);
    }
}

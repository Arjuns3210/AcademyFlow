<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Batch;
use App\Models\Course;
use App\Models\FeeTransaction;
use App\Models\Institute;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Populates the still-empty demo/test institutes (Bright Coaching Classes,
 * 80 day english, wew, demo class) with realistic courses/teachers/batches/
 * students/fees/attendance so their dashboards show non-trivial data.
 * Deliberately skips any institute that already has courses, so a re-run
 * never touches an institute with real data — "rj class" is not in the
 * target list at all for exactly that reason.
 */
class DemoDataSeeder extends Seeder
{
    private const TARGET_INSTITUTES = ['Bright Coaching Classes', '80 day english', 'wew', 'demo class'];

    private const STUDENT_NAMES = [
        'Aarav Sharma', 'Vivaan Gupta', 'Aditi Singh', 'Diya Patel', 'Ishaan Reddy',
        'Ananya Iyer', 'Kabir Khan', 'Saanvi Nair', 'Arjun Mehta', 'Riya Chatterjee',
    ];

    public function run(): void
    {
        foreach (self::TARGET_INSTITUTES as $name) {
            $institute = Institute::where('name', $name)->first();

            if (! $institute) {
                $this->command?->warn("Skipping {$name} — institute not found.");

                continue;
            }

            if (Course::where('tenant_id', $institute->id)->exists()) {
                $this->command?->warn("Skipping {$name} — already has data.");

                continue;
            }

            $this->seedInstitute($institute);
        }
    }

    private function seedInstitute(Institute $institute): void
    {
        $admin = User::where('tenant_id', $institute->id)->firstOrFail();

        $teachers = collect(['Anita Verma', 'Rohit Kapoor'])->map(fn (string $name) => Teacher::create([
            'tenant_id' => $institute->id,
            'name' => $name,
            'qualification' => 'M.A., B.Ed',
            'mobile' => '9'.fake()->numerify('#########'),
            'email' => strtolower(str_replace(' ', '.', $name)).'@example.com',
            'subjects' => fake()->randomElement(['Maths, Science', 'English, Social Studies', 'Hindi, Sanskrit']),
            'status' => 'active',
        ]));

        $courseDefs = [
            ['name' => 'Foundation Batch', 'duration' => '3 months', 'fee' => 6000],
            ['name' => 'Advanced Batch', 'duration' => '6 months', 'fee' => 12000],
            ['name' => 'Crash Course', 'duration' => '1 month', 'fee' => 3000],
        ];

        $courses = collect($courseDefs)->map(fn (array $def) => Course::create([
            'tenant_id' => $institute->id,
            ...$def,
            'status' => 'active',
        ]));

        $timeSlots = ['7:00 AM - 8:30 AM', '4:00 PM - 5:30 PM', '6:00 PM - 7:30 PM'];

        $batches = $courses->values()->map(fn (Course $course, int $i) => Batch::create([
            'tenant_id' => $institute->id,
            'name' => $course->name.' - Batch A',
            'course_id' => $course->id,
            'teacher_id' => $teachers[$i % $teachers->count()]->id,
            'time_slot' => $timeSlots[$i % count($timeSlots)],
            'capacity' => 20,
            'status' => 'active',
        ]));

        foreach (self::STUDENT_NAMES as $i => $name) {
            $batch = $batches[$i % $batches->count()];
            $admissionDate = now()->copy()->subDays(fake()->numberBetween(10, 170));
            $status = match (true) {
                $i === 9 => 'dropped',
                $i === 8 => 'inactive',
                default => 'active',
            };

            $student = Student::create([
                'tenant_id' => $institute->id,
                'name' => $name,
                'dob' => fake()->dateTimeBetween('-16 years', '-6 years')->format('Y-m-d'),
                'gender' => fake()->randomElement(['male', 'female']),
                'student_mobile' => '9'.fake()->numerify('#########'),
                'parent_name' => fake()->name(),
                'parent_mobile' => '9'.fake()->numerify('#########'),
                'parent_email' => fake()->safeEmail(),
                'address' => fake()->address(),
                'course_id' => $batch->course_id,
                'batch_id' => $batch->id,
                'status' => $status,
                'admission_date' => $admissionDate->toDateString(),
            ]);

            $this->seedFees($institute, $student, $admissionDate, $admin);
            $this->seedAttendance($institute, $student, $admissionDate, $admin);
        }
    }

    private function seedFees(Institute $institute, Student $student, Carbon $admissionDate, User $admin): void
    {
        $fee = StudentFee::where('student_id', $student->id)->first();

        if (! $fee || $fee->net_fee <= 0) {
            return;
        }

        // Spread of fully paid / partially paid / not-yet-paid students,
        // so Pending Fees, Today's Collection and the Monthly Collection
        // chart all show a realistic mix rather than all-or-nothing.
        $paidPortion = fake()->randomElement([1, 1, 0.5, 0.5, 0]);
        $amount = round($fee->net_fee * $paidPortion, -2);

        if ($amount <= 0) {
            return;
        }

        $paymentDate = $admissionDate->copy()->addDays(fake()->numberBetween(0, 20));
        if ($paymentDate->gt(now())) {
            $paymentDate = now();
        }

        FeeTransaction::create([
            'tenant_id' => $institute->id,
            'student_fee_id' => $fee->id,
            'amount' => $amount,
            'payment_mode' => fake()->randomElement(['cash', 'upi', 'card', 'bank_transfer']),
            'payment_date' => $paymentDate->toDateString(),
            'remarks' => 'Seeded demo payment',
            'collected_by' => $admin->id,
        ]);
    }

    private function seedAttendance(Institute $institute, Student $student, Carbon $admissionDate, User $admin): void
    {
        if ($student->status !== 'active') {
            return;
        }

        $date = now()->copy();
        $marked = 0;

        while ($marked < 15 && $date->gte($admissionDate)) {
            if ($date->isSunday()) {
                $date->subDay();

                continue;
            }

            Attendance::mark(
                tenantId: $institute->id,
                studentId: $student->id,
                batchId: $student->batch_id,
                attendanceDate: $date->toDateString(),
                status: fake()->randomElement(['present', 'present', 'present', 'absent', 'late']),
                markedBy: $admin->id,
            );

            $marked++;
            $date->subDay();
        }
    }
}

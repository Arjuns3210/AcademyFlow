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
 * Fills the demo institute (Bright Coaching Classes) with a Class 5-10 +
 * Class 11-12 Science/Commerce coaching setup: 10 courses, 10 teachers,
 * 10 batches, 10 students, plus their fee payments and attendance.
 *
 * Every value is hardcoded (no fake()) because faker is a dev-only
 * dependency and this seeder is meant to run on the live server. Skips the
 * institute if it already has courses, so a re-run never duplicates data.
 */
class CoachingDemoDataSeeder extends Seeder
{
    private const INSTITUTE_NAME = 'Bright Coaching Classes';

    private const COURSES = [
        ['name' => 'Class 5', 'duration' => '1 year', 'fee' => 12000, 'description' => 'Maths, Science, English - school syllabus support (CBSE/State Board)'],
        ['name' => 'Class 6', 'duration' => '1 year', 'fee' => 13000, 'description' => 'Maths, Science, English, Social Studies'],
        ['name' => 'Class 7', 'duration' => '1 year', 'fee' => 14000, 'description' => 'Maths, Science, English, Social Studies'],
        ['name' => 'Class 8', 'duration' => '1 year', 'fee' => 15000, 'description' => 'Maths, Science, English, Social Studies + Olympiad basics'],
        ['name' => 'Class 9', 'duration' => '1 year', 'fee' => 18000, 'description' => 'Maths, Science (Physics, Chemistry, Biology), English'],
        ['name' => 'Class 10 Board Prep', 'duration' => '1 year', 'fee' => 22000, 'description' => 'Board exam preparation - Maths, Science, English, weekly tests'],
        ['name' => 'Class 11 Science (PCM/PCB)', 'duration' => '1 year', 'fee' => 30000, 'description' => 'Physics, Chemistry, Maths/Biology'],
        ['name' => 'Class 11 Commerce', 'duration' => '1 year', 'fee' => 24000, 'description' => 'Accountancy, Business Studies, Economics'],
        ['name' => 'Class 12 Science (PCM/PCB)', 'duration' => '1 year', 'fee' => 32000, 'description' => 'Physics, Chemistry, Maths/Biology - Board + entrance basics'],
        ['name' => 'Class 12 Commerce', 'duration' => '1 year', 'fee' => 26000, 'description' => 'Accountancy, Business Studies, Economics - Board prep'],
    ];

    private const TEACHERS = [
        ['name' => 'Sunita Rao', 'qualification' => 'B.Sc, B.Ed', 'subjects' => 'Maths (Class 5-8)', 'mobile' => '9845012301'],
        ['name' => 'Ramesh Kulkarni', 'qualification' => 'M.Sc, B.Ed', 'subjects' => 'Science (Class 5-8)', 'mobile' => '9845012302'],
        ['name' => 'Kavita Joshi', 'qualification' => 'M.A. English, B.Ed', 'subjects' => 'English, Social Studies', 'mobile' => '9845012303'],
        ['name' => 'Deepak Mishra', 'qualification' => 'M.Sc Maths, B.Ed', 'subjects' => 'Maths (Class 9-10)', 'mobile' => '9845012304'],
        ['name' => 'Neha Agarwal', 'qualification' => 'M.Sc, B.Ed', 'subjects' => 'Science (Class 9-10)', 'mobile' => '9845012305'],
        ['name' => 'Vikram Saxena', 'qualification' => 'M.Sc Physics', 'subjects' => 'Physics (Class 11-12)', 'mobile' => '9845012306'],
        ['name' => 'Pooja Menon', 'qualification' => 'M.Sc Chemistry', 'subjects' => 'Chemistry, Biology (Class 11-12)', 'mobile' => '9845012307'],
        ['name' => 'Amit Deshpande', 'qualification' => 'M.Sc Maths', 'subjects' => 'Mathematics (Class 11-12)', 'mobile' => '9845012308'],
        ['name' => 'Shalini Bansal', 'qualification' => 'M.Com, CA Inter', 'subjects' => 'Accountancy, Business Studies', 'mobile' => '9845012309'],
        ['name' => 'Rahul Srinivasan', 'qualification' => 'M.A. Economics', 'subjects' => 'Economics', 'mobile' => '9845012310'],
    ];

    /** One batch per course (same index), each with its own teacher. */
    private const BATCHES = [
        ['name' => 'Class 5 - Evening', 'teacher' => 0, 'time_slot' => '4:00 PM - 5:00 PM', 'capacity' => 25],
        ['name' => 'Class 6 - Evening', 'teacher' => 2, 'time_slot' => '4:00 PM - 5:00 PM', 'capacity' => 25],
        ['name' => 'Class 7 - Evening', 'teacher' => 1, 'time_slot' => '5:00 PM - 6:00 PM', 'capacity' => 25],
        ['name' => 'Class 8 - Evening', 'teacher' => 0, 'time_slot' => '5:00 PM - 6:00 PM', 'capacity' => 25],
        ['name' => 'Class 9 - Evening', 'teacher' => 4, 'time_slot' => '6:00 PM - 7:30 PM', 'capacity' => 30],
        ['name' => 'Class 10 - Board Batch', 'teacher' => 3, 'time_slot' => '6:00 PM - 7:30 PM', 'capacity' => 30],
        ['name' => 'Class 11 Science - Morning', 'teacher' => 5, 'time_slot' => '7:00 AM - 9:00 AM', 'capacity' => 30],
        ['name' => 'Class 11 Commerce - Evening', 'teacher' => 8, 'time_slot' => '5:00 PM - 7:00 PM', 'capacity' => 30],
        ['name' => 'Class 12 Science - Morning', 'teacher' => 7, 'time_slot' => '7:00 AM - 9:00 AM', 'capacity' => 30],
        ['name' => 'Class 12 Commerce - Evening', 'teacher' => 9, 'time_slot' => '7:00 PM - 9:00 PM', 'capacity' => 30],
    ];

    /**
     * One student per batch (same index). `age` matches the class, `joined`
     * is days before today, `paid` is the share of the net fee collected.
     */
    private const STUDENTS = [
        ['name' => 'Aarav Sharma', 'gender' => 'male', 'age' => 10, 'parent' => 'Rajesh Sharma', 'area' => 'Jayanagar', 'joined' => 160, 'paid' => 1.0, 'status' => 'active'],
        ['name' => 'Ananya Iyer', 'gender' => 'female', 'age' => 11, 'parent' => 'Lakshmi Iyer', 'area' => 'Malleshwaram', 'joined' => 150, 'paid' => 0.5, 'status' => 'active'],
        ['name' => 'Vivaan Gupta', 'gender' => 'male', 'age' => 12, 'parent' => 'Sanjay Gupta', 'area' => 'Indiranagar', 'joined' => 140, 'paid' => 1.0, 'status' => 'active'],
        ['name' => 'Diya Patel', 'gender' => 'female', 'age' => 13, 'parent' => 'Hemant Patel', 'area' => 'Koramangala', 'joined' => 120, 'paid' => 0.25, 'status' => 'active'],
        ['name' => 'Ishaan Reddy', 'gender' => 'male', 'age' => 14, 'parent' => 'Srinivas Reddy', 'area' => 'HSR Layout', 'joined' => 110, 'paid' => 0.5, 'status' => 'active'],
        ['name' => 'Saanvi Nair', 'gender' => 'female', 'age' => 15, 'parent' => 'Suresh Nair', 'area' => 'BTM Layout', 'joined' => 95, 'paid' => 1.0, 'status' => 'active'],
        ['name' => 'Arjun Mehta', 'gender' => 'male', 'age' => 16, 'parent' => 'Nitin Mehta', 'area' => 'Whitefield', 'joined' => 75, 'paid' => 0.5, 'status' => 'active'],
        ['name' => 'Riya Chatterjee', 'gender' => 'female', 'age' => 16, 'parent' => 'Debashish Chatterjee', 'area' => 'Banashankari', 'joined' => 60, 'paid' => 0.0, 'status' => 'active'],
        ['name' => 'Kabir Khan', 'gender' => 'male', 'age' => 17, 'parent' => 'Imran Khan', 'area' => 'Rajajinagar', 'joined' => 40, 'paid' => 1.0, 'status' => 'active'],
        ['name' => 'Meera Joshi', 'gender' => 'female', 'age' => 17, 'parent' => 'Anil Joshi', 'area' => 'Basavanagudi', 'joined' => 20, 'paid' => 0.5, 'status' => 'inactive'],
    ];

    private const PAYMENT_MODES = ['upi', 'cash', 'bank_transfer', 'upi', 'cash'];

    public function run(): void
    {
        $institute = Institute::where('name', self::INSTITUTE_NAME)->first();

        if (! $institute) {
            $this->command?->warn('Skipping - '.self::INSTITUTE_NAME.' not found. Run DemoInstituteSeeder first.');

            return;
        }

        if (Course::where('tenant_id', $institute->id)->exists()) {
            $this->command?->warn('Skipping - '.self::INSTITUTE_NAME.' already has data.');

            return;
        }

        $admin = User::where('tenant_id', $institute->id)->firstOrFail();

        $teachers = collect(self::TEACHERS)->map(fn (array $t) => Teacher::create([
            'tenant_id' => $institute->id,
            ...$t,
            'email' => strtolower(str_replace(' ', '.', $t['name'])).'@example.com',
            'status' => 'active',
        ]));

        $courses = collect(self::COURSES)->map(fn (array $c) => Course::create([
            'tenant_id' => $institute->id,
            ...$c,
            'status' => 'active',
        ]));

        $batches = collect(self::BATCHES)->map(fn (array $b, int $i) => Batch::create([
            'tenant_id' => $institute->id,
            'name' => $b['name'],
            'course_id' => $courses[$i]->id,
            'teacher_id' => $teachers[$b['teacher']]->id,
            'time_slot' => $b['time_slot'],
            'capacity' => $b['capacity'],
            'status' => 'active',
        ]));

        foreach (self::STUDENTS as $i => $s) {
            $batch = $batches[$i];
            $admissionDate = now()->startOfDay()->subDays($s['joined']);

            $student = Student::create([
                'tenant_id' => $institute->id,
                'name' => $s['name'],
                'dob' => now()->subYears($s['age'])->subDays(40 + $i * 23)->toDateString(),
                'gender' => $s['gender'],
                'student_mobile' => $s['age'] >= 14 ? '98860'.str_pad((string) (45100 + $i), 5, '0', STR_PAD_LEFT) : null,
                'parent_name' => $s['parent'],
                'parent_mobile' => '99001'.str_pad((string) (67200 + $i), 5, '0', STR_PAD_LEFT),
                'parent_email' => strtolower(str_replace(' ', '.', $s['parent'])).'@example.com',
                'address' => "{$s['area']}, Bengaluru, Karnataka",
                'course_id' => $batch->course_id,
                'batch_id' => $batch->id,
                'status' => $s['status'],
                'admission_date' => $admissionDate->toDateString(),
            ]);

            $this->seedFees($institute, $student, $admissionDate, $s['paid'], $i, $admin);
            $this->seedAttendance($institute, $student, $admissionDate, $i, $admin);
        }
    }

    /**
     * Full payers pay in two installments (half at admission, half a month
     * later) so receipts and the monthly collection chart show a spread.
     */
    private function seedFees(Institute $institute, Student $student, Carbon $admissionDate, float $paidShare, int $i, User $admin): void
    {
        // Auto-created by StudentObserver on admission.
        $fee = StudentFee::where('student_id', $student->id)->first();

        if (! $fee || $fee->net_fee <= 0 || $paidShare <= 0) {
            return;
        }

        $total = round($fee->net_fee * $paidShare, -2);
        $installments = $paidShare >= 1 ? [$total / 2, $total / 2] : [$total];

        foreach ($installments as $n => $amount) {
            $paymentDate = $admissionDate->copy()->addDays($n * 30 + 2);

            if ($paymentDate->gt(now())) {
                $paymentDate = now();
            }

            FeeTransaction::create([
                'tenant_id' => $institute->id,
                'student_fee_id' => $fee->id,
                'amount' => $amount,
                'payment_mode' => self::PAYMENT_MODES[($i + $n) % count(self::PAYMENT_MODES)],
                'payment_date' => $paymentDate->toDateString(),
                'remarks' => count($installments) > 1 ? 'Installment '.($n + 1) : 'Part payment',
                'collected_by' => $admin->id,
            ]);
        }
    }

    /**
     * Last 20 working days (Mon-Sat) for active students, mostly present
     * with a few absent/late/leave days spread deterministically.
     */
    private function seedAttendance(Institute $institute, Student $student, Carbon $admissionDate, int $i, User $admin): void
    {
        if ($student->status !== 'active') {
            return;
        }

        $date = now()->startOfDay()->subDay();
        $marked = 0;

        while ($marked < 20 && $date->gte($admissionDate)) {
            if (! $date->isSunday()) {
                $seed = ($marked * 7 + $i * 3) % 17;

                Attendance::mark(
                    tenantId: $institute->id,
                    studentId: $student->id,
                    batchId: $student->batch_id,
                    attendanceDate: $date->toDateString(),
                    status: match (true) {
                        $seed === 0, $seed === 9 => 'absent',
                        $seed === 4 => 'late',
                        $seed === 13 => 'leave',
                        default => 'present',
                    },
                    markedBy: $admin->id,
                );

                $marked++;
            }

            $date->subDay();
        }
    }
}

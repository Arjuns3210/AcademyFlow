<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\FeeTransaction;
use App\Models\Institute;
use App\Models\InstituteType;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\Subscription;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $studentStats = null;
        $feeStats = null;
        $attendanceStats = null;
        $monthlyCollectionChart = null;
        $admissionTrendChart = null;
        $trialBanner = null;
        $superAdminStats = null;
        $instituteSignupChart = null;
        $instituteTypeChart = null;

        if (Auth::user()->isSuperAdmin()) {
            $superAdminStats = [
                'totalInstitutes' => Institute::count(),
                'activeSubscriptions' => Subscription::where('status', 'active')->count(),
                'trialInstitutes' => Subscription::where('status', 'trial')->count(),
                'expiredOrCancelled' => Subscription::whereIn('status', ['expired', 'cancelled'])->count(),
                'mrr' => $this->monthlyRecurringRevenue(),
            ];

            $instituteSignupChart = $this->instituteSignupSeries();
            $instituteTypeChart = $this->instituteTypeSeries();
        }

        // Independent of every module permission below — any authenticated
        // tenant user should see their institute's own trial state, not just
        // whoever happens to have students/fees/attendance,view.
        if (! Auth::user()->isSuperAdmin()) {
            $subscription = Auth::user()->institute->subscription;

            if ($subscription?->isOnTrial()) {
                $trialBanner = ['daysLeft' => $subscription->daysLeftInTrial()];
            }
        }

        if (! Auth::user()->isSuperAdmin() && Auth::user()->canModule('students', 'view')) {
            $studentStats = [
                'total' => Student::count(),
                'active' => Student::where('status', 'active')->count(),
            ];

            $admissionTrendChart = $this->admissionTrendSeries();
        }

        // Gated independently from students,view — an Accountant-style role
        // may have fees,view without students,view, and vice versa.
        if (! Auth::user()->isSuperAdmin() && Auth::user()->canModule('fees', 'view')) {
            $feeStats = [
                'pending' => StudentFee::whereHas('student', fn ($query) => $query->where('status', 'active'))
                    ->get()
                    ->sum(fn (StudentFee $fee) => $fee->pending_amount),
                'todayCollection' => FeeTransaction::whereDate('payment_date', today())
                    ->whereNull('voided_at')
                    ->sum('amount'),
            ];

            $monthlyCollectionChart = $this->monthlyCollectionSeries();
        }

        // Same independent gating — a Teacher-type role may have
        // attendance,view without fees or students,edit, and vice versa.
        if (! Auth::user()->isSuperAdmin() && Auth::user()->canModule('attendance', 'view')) {
            $todayMarks = Attendance::where('attendance_date', today()->toDateString())->get();

            $attendanceStats = [
                // Distinct from "0 present" — nobody having been marked yet
                // shouldn't read as "everyone's absent".
                'markedToday' => $todayMarks->isNotEmpty(),
                'present' => $todayMarks->where('status', 'present')->count(),
                'total' => Student::where('status', 'active')->count(),
            ];
        }

        return view('dashboard', compact(
            'studentStats',
            'feeStats',
            'attendanceStats',
            'monthlyCollectionChart',
            'admissionTrendChart',
            'trialBanner',
            'superAdminStats',
            'instituteSignupChart',
            'instituteTypeChart'
        ));
    }

    /**
     * Sums amount_paid across every currently-active subscription. A yearly
     * plan's amount_paid covers 12 months, so it's divided by 12 here to
     * normalize it to a monthly figure alongside monthly plans — a
     * simplification (real MRR accounting would prorate a plan change
     * mid-cycle), but reasonable given billing is a manual Super Admin
     * action in this phase, not a metered/prorated gateway integration.
     */
    private function monthlyRecurringRevenue(): float
    {
        return Subscription::where('status', 'active')
            ->with('plan')
            ->get()
            ->sum(function (Subscription $subscription) {
                $amount = (float) $subscription->amount_paid;

                return $subscription->plan?->billing_cycle === 'yearly' ? $amount / 12 : $amount;
            });
    }

    private function instituteSignupSeries(): array
    {
        $rows = Institute::where('created_at', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(created_at) as y, MONTH(created_at) as m, COUNT(*) as total')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => "{$row->y}-{$row->m}");

        return $this->lastSixMonths($rows, fn ($row) => $row ? (int) $row->total : 0);
    }

    /**
     * All-time distribution, not a trend - a pie of "how many institutes
     * right now", so unlike the other charts there's no lastSixMonths()
     * zero-fill. Reuses the same withCount('institutes') already computed
     * for the Institute Types list page. Institutes without a type
     * (institute_type_id is nullable) get their own "Unassigned" slice
     * instead of silently vanishing from the total.
     */
    private function instituteTypeSeries(): array
    {
        $series = InstituteType::withCount('institutes')
            ->orderByDesc('institutes_count')
            ->get()
            ->map(fn (InstituteType $type) => ['label' => $type->name, 'value' => $type->institutes_count])
            ->values()
            ->all();

        $unassigned = Institute::whereNull('institute_type_id')->count();

        if ($unassigned > 0) {
            $series[] = ['label' => 'Unassigned', 'value' => $unassigned];
        }

        return $series;
    }

    private function monthlyCollectionSeries(): array
    {
        $rows = FeeTransaction::whereNull('voided_at')
            ->where('payment_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(payment_date) as y, MONTH(payment_date) as m, SUM(amount) as total')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => "{$row->y}-{$row->m}");

        return $this->lastSixMonths($rows, fn ($row) => $row ? (float) $row->total : 0.0);
    }

    private function admissionTrendSeries(): array
    {
        $rows = Student::where('admission_date', '>=', now()->subMonths(5)->startOfMonth())
            ->selectRaw('YEAR(admission_date) as y, MONTH(admission_date) as m, COUNT(*) as total')
            ->groupBy('y', 'm')
            ->get()
            ->keyBy(fn ($row) => "{$row->y}-{$row->m}");

        return $this->lastSixMonths($rows, fn ($row) => $row ? (int) $row->total : 0);
    }

    /**
     * Zero-filled [label, value] series for the current month plus the
     * preceding 5, so a month with no activity still renders as a bar
     * instead of silently shifting the axis.
     */
    private function lastSixMonths(Collection $rows, callable $valueFor): array
    {
        return collect(range(5, 0))->map(function (int $i) use ($rows, $valueFor) {
            $month = now()->subMonths($i);
            $row = $rows->get("{$month->year}-{$month->month}");

            return [
                'label' => $month->format('M Y'),
                'value' => $valueFor($row),
            ];
        })->values()->all();
    }
}

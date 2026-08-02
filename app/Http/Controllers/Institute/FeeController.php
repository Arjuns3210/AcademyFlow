<?php

namespace App\Http\Controllers\Institute;

use App\Events\FeeDueEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Institute\StorePaymentRequest;
use App\Http\Requests\Institute\UpdateDiscountRequest;
use App\Http\Requests\Institute\UpdateInstallmentsRequest;
use App\Http\Requests\Institute\VoidTransactionRequest;
use App\Models\ActivityLog;
use App\Models\FeeTransaction;
use App\Models\StudentFee;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class FeeController extends Controller
{
    /**
     * status is a computed accessor (not a DB column — see StudentFee),
     * so filtering by it can't happen in SQL. Fetches this tenant's fee
     * rows in full and filters/paginates in memory instead, matching the
     * "compute live, don't over-engineer for this scale" precedent already
     * set by Batch::enrolledCount().
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $statusFilter = (string) $request->query('status', '');

        // withTrashed() on student — a soft-deleted student's fee row must
        // still show their name here instead of crashing (same fix already
        // applied to the Fee/Collection Reports in ReportController).
        $query = StudentFee::with(['student' => fn ($q) => $q->withTrashed(), 'transactions']);

        if ($search !== '') {
            $query->whereHas('student', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('student_code', 'like', "%{$search}%");
            });
        }

        $fees = $query->get()->sortBy(fn (StudentFee $fee) => $fee->student?->name);

        if ($statusFilter !== '') {
            $fees = $fees->filter(fn (StudentFee $fee) => $fee->status === $statusFilter);
        }

        return view('institute.fees.index', [
            'fees' => $this->paginateCollection($fees->values(), $request),
            'search' => $search,
            'statusFilter' => $statusFilter,
            'moduleLabel' => Auth::user()->institute->moduleLabel('fees'),
        ]);
    }

    public function show(StudentFee $studentFee): View
    {
        $studentFee->load([
            'student' => fn ($q) => $q->withTrashed(),
            'transactions' => function ($query) {
                $query->orderByDesc('payment_date')->orderByDesc('id')->with(['collectedBy', 'voidedBy']);
            },
        ]);

        return view('institute.fees.show', [
            'studentFee' => $studentFee,
            'moduleLabel' => Auth::user()->institute->moduleLabel('fees'),
        ]);
    }

    public function storePayment(StorePaymentRequest $request, StudentFee $studentFee): RedirectResponse
    {
        $data = $request->validated();

        $transaction = FeeTransaction::create([
            'tenant_id' => Auth::user()->tenant_id,
            'student_fee_id' => $studentFee->id,
            'amount' => $data['amount'],
            'payment_mode' => $data['payment_mode'],
            'payment_date' => $data['payment_date'] ?? now()->toDateString(),
            'remarks' => $data['remarks'] ?? null,
            'collected_by' => Auth::id(),
        ]);

        return redirect()->route('institute.fees.show', $studentFee)
            ->with('status', "Payment of ₹{$transaction->amount} recorded — receipt {$transaction->receipt_number}.")
            ->with('lastReceiptTransactionId', $transaction->id);
    }

    public function updateDiscount(UpdateDiscountRequest $request, StudentFee $studentFee): RedirectResponse
    {
        $data = $request->validated();
        $oldDiscount = (float) $studentFee->discount_amount;
        $oldReason = $studentFee->discount_reason;

        $studentFee->update([
            'discount_amount' => $data['discount_amount'],
            'discount_reason' => $data['discount_reason'] ?? null,
        ]);

        ActivityLog::record(
            'discount_updated',
            $studentFee,
            sprintf(
                'Discount changed from ₹%s (%s) to ₹%s (%s) for %s.',
                number_format($oldDiscount, 2),
                $oldReason ?: 'no reason given',
                number_format((float) $data['discount_amount'], 2),
                $data['discount_reason'] ?? 'no reason given',
                $studentFee->student()->withTrashed()->value('name') ?? 'this student',
            )
        );

        return redirect()->route('institute.fees.show', $studentFee)->with('status', 'Discount updated.');
    }

    public function updateInstallments(UpdateInstallmentsRequest $request, StudentFee $studentFee): RedirectResponse
    {
        $studentFee->update($request->validated());

        return redirect()->route('institute.fees.show', $studentFee)->with('status', 'Installment note updated.');
    }

    /**
     * Manual trigger — dispatches the same FeeDueEvent an eventual automatic
     * scan would, so both paths share one access-check + send-logic
     * (SendFeeDueWhatsAppReminder), not two copies of it. due_date is
     * free text from the sender, not a stored field — there's no due_date
     * concept on StudentFee yet (see FeeDueEvent's docblock).
     */
    public function sendReminder(Request $request, StudentFee $studentFee): RedirectResponse
    {
        $dueDateLabel = trim((string) $request->input('due_date_label', ''));

        \Log::info("Manual fee reminder trigger for StudentFee ID {$studentFee->id} (Student ID {$studentFee->student_id}) by user ID " . Auth::id() . ", due date label: '{$dueDateLabel}'");
        event(new FeeDueEvent(Auth::user()->institute, $studentFee, $dueDateLabel !== '' ? $dueDateLabel : null));

        return redirect()->route('institute.fees.show', $studentFee)->with('status', 'Fee reminder queued.');
    }

    /**
     * Same event/listener as sendReminder() above, just looped over a
     * checkbox-selected batch from the fee list. StudentFee::whereIn() is
     * still auto-scoped to the current tenant by BelongsToTenant's global
     * scope, so a submitted id from another institute simply never matches.
     */
    public function sendBulkReminders(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'student_fee_ids' => ['required', 'array', 'min:1'],
            'student_fee_ids.*' => ['integer'],
            'due_date_label' => ['nullable', 'string', 'max:255'],
        ]);

        $dueDateLabel = trim((string) ($data['due_date_label'] ?? ''));
        $institute = Auth::user()->institute;

        $studentFees = StudentFee::whereIn('id', $data['student_fee_ids'])->get();

        foreach ($studentFees as $studentFee) {
            event(new FeeDueEvent($institute, $studentFee, $dueDateLabel !== '' ? $dueDateLabel : null));
        }

        return redirect()->route('institute.reminders.index')
            ->with('status', "Fee reminder queued for {$studentFees->count()} student(s).");
    }

    public function voidTransaction(VoidTransactionRequest $request, FeeTransaction $transaction): RedirectResponse
    {
        abort_if($transaction->isVoided(), 422, 'This transaction has already been voided.');

        $transaction->void(Auth::user(), $request->validated('void_reason'));

        return redirect()->route('institute.fees.show', $transaction->student_fee_id)
            ->with('status', "Transaction {$transaction->receipt_number} voided.");
    }

    public function receipt(FeeTransaction $transaction): Response
    {
        abort_if($transaction->isVoided(), 403, 'Voided transactions cannot be printed.');

        $transaction->load(['studentFee.student' => fn ($q) => $q->withTrashed(), 'collectedBy']);

        $institute = Auth::user()->institute;
        $logoDataUri = null;

        if ($institute->logo && Storage::disk('public')->exists($institute->logo)) {
            $path = Storage::disk('public')->path($institute->logo);
            $logoDataUri = 'data:'.mime_content_type($path).';base64,'.base64_encode(file_get_contents($path));
        }

        $pdf = Pdf::loadView('institute.fees.receipt', [
            'transaction' => $transaction,
            'institute' => $institute,
            'logoDataUri' => $logoDataUri,
        ]);

        return $pdf->download("receipt-{$transaction->receipt_number}.pdf");
    }
}

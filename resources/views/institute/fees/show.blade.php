<x-tenant-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h2 class="h4 mb-0">{{ $studentFee->student->name }}</h2>
                <div class="text-muted small"><code>{{ $studentFee->student->student_code }}</code></div>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <x-back-link :href="route('institute.fees.index')" />
                @canAccessModule('whatsapp_reminder')
                    @canModule('fees', 'view')
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#sendReminderModal">
                            Send Reminder
                        </button>
                    @endcanModule
                @endcanAccessModule
                @canModule('fees', 'edit')
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#editDiscountModal">
                        Edit Discount
                    </button>
                @endcanModule
            </div>
        </div>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success d-flex justify-content-between align-items-center">
            <span>{{ session('status') }}</span>
            @if (session('lastReceiptTransactionId'))
                <a href="{{ route('institute.fees.transactions.receipt', session('lastReceiptTransactionId')) }}" class="btn btn-sm btn-outline-secondary">
                    Print Receipt
                </a>
            @endif
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <h3 class="h6 text-uppercase text-muted mb-3">Fee Breakdown</h3>
                    <dl class="row mb-0">
                        <dt class="col-sm-6">Total Fee</dt>
                        <dd class="col-sm-6 text-end">₹{{ number_format($studentFee->total_fee, 2) }}</dd>

                        <dt class="col-sm-6">Discount</dt>
                        <dd class="col-sm-6 text-end">
                            ₹{{ number_format($studentFee->discount_amount, 2) }}
                            @if ($studentFee->discount_reason)
                                <div class="text-muted small">{{ $studentFee->discount_reason }}</div>
                            @endif
                        </dd>

                        <dt class="col-sm-6 fw-semibold">Net Fee</dt>
                        <dd class="col-sm-6 text-end fw-semibold">₹{{ number_format($studentFee->net_fee, 2) }}</dd>

                        <dt class="col-sm-6">Paid</dt>
                        <dd class="col-sm-6 text-end">₹{{ number_format($studentFee->paid_amount, 2) }}</dd>

                        <dt class="col-sm-6">Pending</dt>
                        <dd class="col-sm-6 text-end {{ $studentFee->pending_amount > 0 ? 'text-danger fw-semibold' : '' }}">
                            ₹{{ number_format($studentFee->pending_amount, 2) }}
                        </dd>

                        <dt class="col-sm-6">Status</dt>
                        <dd class="col-sm-6 text-end">
                            <span class="badge {{ match($studentFee->status) { 'paid' => 'bg-success', 'partial' => 'bg-warning text-dark', 'pending' => 'bg-danger' } }}">
                                {{ ucfirst($studentFee->status) }}
                            </span>
                        </dd>
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="h6 text-uppercase text-muted mb-0">Installments</h3>
                        @canModule('fees', 'edit')
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInstallmentsModal">
                                Edit
                            </button>
                        @endcanModule
                    </div>

                    @if ($studentFee->installments_allowed)
                        <p class="mb-0" style="white-space: pre-line;">{{ $studentFee->installment_notes ?: 'No schedule note added yet.' }}</p>
                    @else
                        <p class="text-muted mb-0">Installments not enabled for this student.</p>
                    @endif
                </div>
            </div>
        </div>

        @canModule('fees', 'add')
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h3 class="h6 text-uppercase text-muted mb-3">Collect Payment</h3>
                        <form action="{{ route('institute.fees.payments.store', $studentFee) }}" method="POST" class="row g-2 align-items-end">
                            @csrf
                            <div class="col-md-2">
                                <x-input-label for="amount" value="Amount" />
                                <x-text-input id="amount" name="amount" type="number" step="0.01" min="0.01" class="w-100" :value="old('amount')" required />
                                <x-input-error :messages="$errors->get('amount')" />
                            </div>
                            <div class="col-md-2">
                                <x-input-label for="payment_mode" value="Mode" />
                                <select name="payment_mode" id="payment_mode" class="form-select">
                                    <option value="cash" {{ old('payment_mode') === 'cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="upi" {{ old('payment_mode') === 'upi' ? 'selected' : '' }}>UPI</option>
                                    <option value="card" {{ old('payment_mode') === 'card' ? 'selected' : '' }}>Card</option>
                                    <option value="bank_transfer" {{ old('payment_mode') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                    <option value="cheque" {{ old('payment_mode') === 'cheque' ? 'selected' : '' }}>Cheque</option>
                                    <option value="other" {{ old('payment_mode') === 'other' ? 'selected' : '' }}>Other</option>
                                </select>
                                <x-input-error :messages="$errors->get('payment_mode')" />
                            </div>
                            <div class="col-md-2">
                                <x-input-label for="payment_date" value="Date" />
                                <x-text-input id="payment_date" name="payment_date" type="date" class="w-100" :value="old('payment_date', now()->toDateString())" />
                                <x-input-error :messages="$errors->get('payment_date')" />
                            </div>
                            <div class="col-md-4">
                                <x-input-label for="remarks" value="Remarks (optional)" />
                                <x-text-input id="remarks" name="remarks" type="text" class="w-100" :value="old('remarks')" />
                                <x-input-error :messages="$errors->get('remarks')" />
                            </div>
                            <div class="col-md-2">
                                <x-primary-button class="w-100 justify-content-center">Collect</x-primary-button>
                            </div>
                        </form>
                        <div class="text-muted small mt-2">Pending balance: ₹{{ number_format($studentFee->pending_amount, 2) }}</div>
                    </div>
                </div>
            </div>
        @endcanModule

        <div class="col-12">
            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Sr No</th>
                                <th>Date</th>
                                <th class="text-end">Amount</th>
                                <th>Mode</th>
                                <th>Receipt No.</th>
                                <th>Collected By</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($studentFee->transactions as $transaction)
                                <tr class="{{ $transaction->isVoided() ? 'text-muted' : '' }}">
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="{{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">{{ $transaction->payment_date->format('d M Y') }}</td>
                                    <td class="text-end {{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">₹{{ number_format($transaction->amount, 2) }}</td>
                                    <td class="{{ $transaction->isVoided() ? 'text-decoration-line-through' : '' }}">{{ $transaction->paymentModeLabel() }}</td>
                                    <td><code>{{ $transaction->receipt_number }}</code></td>
                                    <td>{{ $transaction->collectedBy?->name ?? '—' }}</td>
                                    <td>
                                        @if ($transaction->isVoided())
                                            <span class="badge bg-danger" title="{{ $transaction->void_reason }} — by {{ $transaction->voidedBy?->name }}">VOIDED</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        @if ($transaction->isVoided())
                                            <button type="button" class="btn btn-sm btn-outline-secondary" disabled title="Voided transactions cannot be printed">Print</button>
                                        @else
                                            <a href="{{ route('institute.fees.transactions.receipt', $transaction) }}" class="btn btn-sm btn-outline-secondary">Print</a>
                                            @canModule('fees', 'delete')
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#voidModal{{ $transaction->id }}">Void</button>
                                            @endcanModule
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">No payments recorded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @canAccessModule('whatsapp_reminder')
        @canModule('fees', 'view')
            <div class="modal fade" id="sendReminderModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('institute.fees.send-reminder', $studentFee) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h2 class="h5 modal-title">Send Fee Reminder</h2>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">
                                    Sends a WhatsApp reminder to {{ $studentFee->student->parent_name ?: $studentFee->student->name }}
                                    @if ($studentFee->student->parent_mobile)
                                        at {{ $studentFee->student->parent_mobile }}
                                    @endif
                                    for the pending balance of ₹{{ number_format($studentFee->pending_amount, 2) }}.
                                </p>
                                <div class="mb-0">
                                    <x-input-label for="due_date_label" value="Due date (optional)" />
                                    <x-text-input id="due_date_label" name="due_date_label" type="text" class="w-100" placeholder="e.g. 5th August" />
                                </div>
                            </div>
                            <div class="modal-footer">
                                <x-secondary-button type="button" data-bs-dismiss="modal">Cancel</x-secondary-button>
                                <x-primary-button>Send Reminder</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endcanModule
    @endcanAccessModule

    @canModule('fees', 'edit')
        <div class="modal fade {{ $errors->hasAny(['discount_amount', 'discount_reason']) ? 'show d-block' : '' }}" id="editDiscountModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('institute.fees.discount.update', $studentFee) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h2 class="h5 modal-title">Edit Discount</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <x-input-label for="discount_amount" value="Discount Amount" />
                                <x-text-input id="discount_amount" name="discount_amount" type="number" step="0.01" min="0" class="w-100" :value="old('discount_amount', $studentFee->discount_amount)" required />
                                <x-input-error :messages="$errors->get('discount_amount')" />
                            </div>
                            <div class="mb-3">
                                <x-input-label for="discount_reason" value="Reason" />
                                <x-text-input id="discount_reason" name="discount_reason" type="text" class="w-100" :value="old('discount_reason', $studentFee->discount_reason)" />
                                <x-input-error :messages="$errors->get('discount_reason')" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <x-secondary-button type="button" data-bs-dismiss="modal">Cancel</x-secondary-button>
                            <x-primary-button>Save</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade {{ $errors->hasAny(['installments_allowed', 'installment_notes']) ? 'show d-block' : '' }}" id="editInstallmentsModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="{{ route('institute.fees.installments.update', $studentFee) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h2 class="h5 modal-title">Edit Installment Note</h2>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="form-check mb-3">
                                <input type="hidden" name="installments_allowed" value="0">
                                <input type="checkbox" name="installments_allowed" id="installments_allowed" class="form-check-input" value="1" {{ old('installments_allowed', $studentFee->installments_allowed) ? 'checked' : '' }}>
                                <label class="form-check-label" for="installments_allowed">Installments allowed for this student</label>
                            </div>
                            <div class="mb-3">
                                <x-input-label for="installment_notes" value="Schedule note (reference only, not enforced)" />
                                <textarea name="installment_notes" id="installment_notes" class="form-control" rows="3" placeholder="e.g. 3 installments of ₹1000, due by 5th of each month">{{ old('installment_notes', $studentFee->installment_notes) }}</textarea>
                                <x-input-error :messages="$errors->get('installment_notes')" />
                            </div>
                        </div>
                        <div class="modal-footer">
                            <x-secondary-button type="button" data-bs-dismiss="modal">Cancel</x-secondary-button>
                            <x-primary-button>Save</x-primary-button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endcanModule

    @canModule('fees', 'delete')
        @foreach ($studentFee->transactions as $transaction)
            @if (! $transaction->isVoided())
                <div class="modal fade" id="voidModal{{ $transaction->id }}" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form action="{{ route('institute.fees.transactions.void', $transaction) }}" method="POST">
                                @csrf
                                @method('PATCH')
                                <div class="modal-header">
                                    <h2 class="h5 modal-title">Void Transaction {{ $transaction->receipt_number }}</h2>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted small">This keeps the transaction visible in the history (struck through) but excludes it from the paid amount. This cannot be undone.</p>
                                    <x-input-label for="void_reason{{ $transaction->id }}" value="Reason" />
                                    <x-text-input id="void_reason{{ $transaction->id }}" name="void_reason" type="text" class="w-100" required />
                                </div>
                                <div class="modal-footer">
                                    <x-secondary-button type="button" data-bs-dismiss="modal">Cancel</x-secondary-button>
                                    <x-danger-button>Void</x-danger-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endforeach
    @endcanModule
</x-tenant-layout>

<x-tenant-layout>
    <x-slot name="header">
        <h2 class="h4 mb-0">WhatsApp Reminders</h2>
    </x-slot>

    @if (session('status'))
        <div class="alert alert-success">{{ session('status') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <h3 class="h6 text-uppercase text-muted mb-2">Connection Status</h3>

            @if ($connectionStatus === 'own')
                <span class="badge bg-success">Connected</span>
                <span class="text-muted small ms-2">Using your institute's own WhatsApp number.</span>
            @elseif ($connectionStatus === 'demo')
                <span class="badge bg-info text-dark">Connected (Demo)</span>
                <span class="text-muted small ms-2">Using AcademyFlow's shared demo number for now — contact us to connect your institute's own number.</span>
            @else
                <span class="badge bg-secondary">Not Connected</span>
                <span class="text-muted small ms-2">WhatsApp reminders aren't active for your institute yet — contact us to get started.</span>
            @endif
        </div>
    </div>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-hover mb-0 align-middle">
                <thead>
                    <tr>
                        <th>Sr No</th>
                        <th>Date</th>
                        <th>Recipient</th>
                        <th>Student</th>
                        <th>Template</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td>{{ $logs->firstItem() + $loop->index }}</td>
                            <td>{{ $log->created_at->format('d M Y, h:i A') }}</td>
                            <td>{{ $log->recipient_number }}</td>
                            <td>{{ $log->student?->name ?? '—' }}</td>
                            <td>{{ ucwords(str_replace('_', ' ', $log->template_name)) }}</td>
                            <td>
                                <span
                                    class="badge {{ match ($log->status) { 'sent', 'delivered', 'read' => 'bg-success', 'failed' => 'bg-danger', default => 'bg-secondary' } }}"
                                    @if ($log->error_message) title="{{ $log->error_message }}" @endif
                                >
                                    {{ ucfirst($log->status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No reminders sent yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-3">
        {{ $logs->links() }}
    </div>
</x-tenant-layout>

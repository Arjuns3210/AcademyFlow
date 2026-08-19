<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $transaction->receipt_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; margin: 0; padding: 30px; }
        .header { width: 100%; margin-bottom: 20px; }
        .header td { vertical-align: middle; }
        .logo { height: 50px; }
        .institute-name { font-size: 18px; font-weight: bold; }
        h1 { font-size: 16px; text-align: center; text-transform: uppercase; letter-spacing: 1px; margin: 20px 0; border-top: 1px solid #999; border-bottom: 1px solid #999; padding: 8px 0; }
        table.details { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        table.details td { padding: 6px 4px; border-bottom: 1px solid #eee; }
        table.details td.label { color: #666; width: 40%; }
        table.details td.value { font-weight: bold; text-align: right; }
        .amount-row td { font-size: 15px; padding-top: 10px; padding-bottom: 10px; }
        .footer { margin-top: 40px; padding-top: 10px; border-top: 1px solid #999; font-size: 10px; color: #666; text-align: center; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            @if ($logoDataUri)
                <td style="width: 60px;">
                    <img class="logo" src="{{ $logoDataUri }}">
                </td>
            @endif
            <td>
                <div class="institute-name">{{ $institute->name }}</div>
                @if ($institute->address)
                    <div>{{ $institute->address }}</div>
                @endif
            </td>
        </tr>
    </table>

    <h1>Fee Receipt</h1>

    <table class="details">
        <tr>
            <td class="label">Receipt No.</td>
            <td class="value">{{ $transaction->receipt_number }}</td>
        </tr>
        <tr>
            <td class="label">Date</td>
            <td class="value">{{ $transaction->payment_date->format('d M Y') }}</td>
        </tr>
        <tr>
            <td class="label">Student</td>
            <td class="value">{{ $transaction->studentFee->student->name }} ({{ $transaction->studentFee->student->student_code }})</td>
        </tr>
        <tr>
            <td class="label">Payment Mode</td>
            <td class="value">{{ $transaction->paymentModeLabel() }}</td>
        </tr>
        <tr>
            <td class="label">Collected By</td>
            <td class="value">{{ $transaction->collectedBy?->name ?? '—' }}</td>
        </tr>
        <tr class="amount-row">
            <td class="label">Amount Paid</td>
            <td class="value">₹{{ number_format($transaction->amount, 2) }}</td>
        </tr>
    </table>

    <div class="footer">
        {{ $institute->name }}
        @if ($institute->contact) &middot; {{ $institute->contact }} @endif
        @if ($institute->email) &middot; {{ $institute->email }} @endif
    </div>
</body>
</html>

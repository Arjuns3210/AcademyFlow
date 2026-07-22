<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #222; margin: 0; padding: 24px; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .subtitle { font-size: 10px; color: #666; margin-bottom: 14px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f2f2f2; }
        .text-end { text-align: right; }
        .summary { margin-top: 10px; font-size: 11px; font-weight: bold; text-align: right; }
        .footer { margin-top: 16px; font-size: 8px; color: #999; }
    </style>
</head>
<body>
    <h1>{{ $title }}</h1>
    @if ($subtitle ?? null)
        <div class="subtitle">{{ $subtitle }}</div>
    @endif

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}">No data for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if ($summary ?? null)
        <div class="summary">{{ $summary }}</div>
    @endif

    <div class="footer">Generated {{ now()->format('d M Y, H:i') }} — {{ $institute->name }}</div>
</body>
</html>

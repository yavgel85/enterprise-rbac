<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Pipeline report — {{ $tenant->name }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1f2937; font-size: 12px; margin: 0; }
        h1 { font-size: 20px; margin: 0 0 2px; }
        h2 { font-size: 14px; margin: 18px 0 6px; border-bottom: 1px solid #e5e7eb; padding-bottom: 4px; }
        .muted { color: #6b7280; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { text-align: left; padding: 5px 6px; border-bottom: 1px solid #e5e7eb; }
        th { background: #f9fafb; font-size: 10px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        td.num, th.num { text-align: right; }
        .cards { width: 100%; margin-top: 8px; }
        .cards td { border: 1px solid #e5e7eb; padding: 8px; width: 25%; }
        .card-label { color: #6b7280; font-size: 10px; text-transform: uppercase; }
        .card-value { font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Pipeline analytics</h1>
    <div class="muted">{{ $tenant->name }} · generated {{ $generatedAt->format('Y-m-d H:i') }}</div>

    <table class="cards">
        <tr>
            <td><div class="card-label">Total deals</div><div class="card-value">{{ number_format($report['totals']['deals']) }}</div></td>
            <td><div class="card-label">Open pipeline</div><div class="card-value">{{ number_format($report['totals']['open_amount'], 0) }}</div></td>
            <td><div class="card-label">Won value</div><div class="card-value">{{ number_format($report['totals']['won_amount'], 0) }}</div></td>
            <td><div class="card-label">Avg cycle (won)</div><div class="card-value">{{ $report['averages']['cycle_days'] !== null ? $report['averages']['cycle_days'].' d' : '—' }}</div></td>
        </tr>
    </table>

    <h2>Conversion funnel</h2>
    <table>
        <thead><tr><th>Stage</th><th class="num">Deals</th><th class="num">Amount</th></tr></thead>
        <tbody>
            @foreach ($report['funnel'] as $row)
                <tr>
                    <td>{{ $row['label'] }}</td>
                    <td class="num">{{ number_format($row['count']) }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>Total amount per owner</h2>
    <table>
        <thead><tr><th>Owner</th><th class="num">Deals</th><th class="num">Amount</th></tr></thead>
        <tbody>
            @forelse ($report['per_owner'] as $row)
                <tr>
                    <td>{{ $row['owner'] }}</td>
                    <td class="num">{{ number_format($row['count']) }}</td>
                    <td class="num">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No deals.</td></tr>
            @endforelse
        </tbody>
    </table>

    <h2>Win / loss ({{ $report['win_loss']['won'] }} won · {{ $report['win_loss']['lost'] }} lost)</h2>
    <table>
        <thead><tr><th>Loss reason</th><th class="num">Deals</th></tr></thead>
        <tbody>
            @forelse ($report['win_loss']['reasons'] as $reason)
                <tr><td>{{ $reason['reason'] }}</td><td class="num">{{ $reason['count'] }}</td></tr>
            @empty
                <tr><td colspan="2" class="muted">No lost deals recorded.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>

@extends('layouts.app')
@section('title', 'Pipeline analytics')
@section('header', 'Pipeline analytics')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Win/loss, conversion funnel and owner performance for {{ $tenant->name }}.</p>
        <a href="{{ route('crm.reports.analytics.pdf', $tenant) }}"
            class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Download PDF</a>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <div class="text-xs uppercase tracking-wider text-gray-400">Total deals</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($report['totals']['deals']) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <div class="text-xs uppercase tracking-wider text-gray-400">Open pipeline</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">{{ number_format($report['totals']['open_amount'], 0) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <div class="text-xs uppercase tracking-wider text-gray-400">Won value</div>
            <div class="mt-1 text-2xl font-semibold text-green-600">{{ number_format($report['totals']['won_amount'], 0) }}</div>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
            <div class="text-xs uppercase tracking-wider text-gray-400">Avg. cycle (won)</div>
            <div class="mt-1 text-2xl font-semibold text-gray-900">
                {{ $report['averages']['cycle_days'] !== null ? $report['averages']['cycle_days'].' d' : '—' }}
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Conversion funnel</h3>
            <canvas id="funnelChart" height="220"></canvas>
        </div>
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Total amount per owner</h3>
            <canvas id="ownerChart" height="220"></canvas>
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">
            Win / loss
            <span class="ml-2 text-sm font-normal text-gray-500">
                {{ $report['win_loss']['won'] }} won · {{ $report['win_loss']['lost'] }} lost
            </span>
        </h3>
        @if (count($report['win_loss']['reasons']) > 0)
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200">
                        <th class="py-2">Loss reason</th>
                        <th class="py-2 text-right">Deals</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($report['win_loss']['reasons'] as $reason)
                        <tr>
                            <td class="py-2">{{ $reason['reason'] }}</td>
                            <td class="py-2 text-right">{{ $reason['count'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-gray-500">No lost deals recorded yet.</p>
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
    <script>
        const report = @json($report);

        const funnel = report.funnel;
        new Chart(document.getElementById('funnelChart'), {
            type: 'bar',
            data: {
                labels: funnel.map(f => f.label),
                datasets: [{
                    label: 'Deals',
                    data: funnel.map(f => f.count),
                    backgroundColor: '#6366f1',
                }],
            },
            options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true } } },
        });

        const owners = report.per_owner;
        new Chart(document.getElementById('ownerChart'), {
            type: 'bar',
            data: {
                labels: owners.map(o => o.owner),
                datasets: [{
                    label: 'Amount',
                    data: owners.map(o => o.amount),
                    backgroundColor: '#10b981',
                }],
            },
            options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } },
        });
    </script>
@endpush

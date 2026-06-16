@extends('layouts.app')
@section('title', 'Observability')
@section('header', 'Observability')

@section('content')
    @php
        $cards = [
            ['label' => 'Active tenants', 'value' => $stats['tenants_active'].' / '.$stats['tenants_total'], 'tone' => 'indigo'],
            ['label' => 'Users', 'value' => $stats['users_total'], 'tone' => 'gray'],
            ['label' => 'Locked users', 'value' => $stats['users_locked'], 'tone' => $stats['users_locked'] > 0 ? 'amber' : 'gray'],
            ['label' => 'Audit events (24h)', 'value' => $stats['audit_24h'], 'tone' => 'gray'],
            ['label' => 'Failed logins (24h)', 'value' => $stats['failed_logins_24h'], 'tone' => $stats['failed_logins_24h'] > 0 ? 'red' : 'gray'],
            ['label' => 'Permission denials (24h)', 'value' => $stats['denied_24h'], 'tone' => $stats['denied_24h'] > 0 ? 'amber' : 'gray'],
            ['label' => 'Failed jobs', 'value' => $failedJobs, 'tone' => $failedJobs > 0 ? 'red' : 'gray'],
        ];
        $tones = [
            'indigo' => 'text-indigo-600',
            'gray' => 'text-gray-900',
            'amber' => 'text-amber-600',
            'red' => 'text-red-600',
        ];
    @endphp

    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        @foreach ($cards as $card)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-4">
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $card['label'] }}</div>
                <div class="mt-2 text-2xl font-bold {{ $tones[$card['tone']] }}">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Audit volume (14 days)</h3>
            @php($max = $eventsByDay->max() ?: 1)
            @if ($eventsByDay->isEmpty())
                <p class="text-sm text-gray-500">No audit events recorded yet.</p>
            @else
                <div class="flex items-end gap-1 h-40">
                    @foreach ($eventsByDay as $day => $total)
                        <div class="flex-1 flex flex-col items-center justify-end group">
                            <div class="w-full rounded-t bg-indigo-500 hover:bg-indigo-600" style="height: {{ max(4, (int) round($total / $max * 150)) }}px" title="{{ $day }}: {{ $total }}"></div>
                            <div class="mt-1 text-[10px] text-gray-400 rotate-0">{{ \Illuminate\Support\Str::of($day)->afterLast('-') }}</div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Top actions (24h)</h3>
            @forelse ($topActions as $action => $total)
                <div class="flex items-center justify-between py-1 text-sm">
                    <span class="text-gray-700 font-mono text-xs">{{ $action }}</span>
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-700">{{ $total }}</span>
                </div>
            @empty
                <p class="text-sm text-gray-500">No activity in the last 24h.</p>
            @endforelse
        </div>
    </div>

    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Recent security events</h3>
        @forelse ($securityFeed as $log)
            <div class="flex items-center justify-between py-2 border-t border-gray-100 first:border-t-0 text-sm">
                <div>
                    <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700 ring-1 ring-red-200">{{ $log->action }}</span>
                    <span class="ml-2 text-gray-600">{{ $log->user?->email ?? 'unknown' }}</span>
                    <span class="ml-1 text-gray-400">@ {{ $log->tenant?->name ?? '—' }}</span>
                </div>
                <div class="text-xs text-gray-400">{{ $log->created_at?->diffForHumans() }} · {{ $log->ip_address }}</div>
            </div>
        @empty
            <p class="text-sm text-gray-500">No security events recorded.</p>
        @endforelse
    </div>
@endsection

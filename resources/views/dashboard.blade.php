@extends('layouts.app')

@section('title', $tenant->name.' dashboard')
@section('header', $tenant->name)

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        @foreach (['Users' => $stats['users'], 'Companies' => $stats['companies'], 'Contacts' => $stats['contacts'], 'Deals' => $stats['deals'], 'Activities' => $stats['activities']] as $label => $value)
            <div class="bg-white rounded-lg border border-gray-200 px-5 py-4 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 {{ $canViewFeed ? 'lg:grid-cols-3' : '' }} gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm {{ $canViewFeed ? 'lg:col-span-2' : '' }}">
            <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-base font-semibold text-gray-900">Recent deals</h3>
                <a href="{{ route('crm.deals.index', $tenant) }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
            </div>
            <div class="divide-y divide-gray-200">
                @forelse ($recentDeals as $deal)
                    <div class="px-6 py-3 flex items-center justify-between text-sm">
                        <div>
                            <div class="font-medium text-gray-900">{{ $deal->title }}</div>
                            <div class="text-gray-500">{{ $deal->company?->name ?? '—' }} · owner {{ $deal->owner?->name ?? '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-medium">{{ number_format((float) $deal->amount, 2) }} {{ $deal->currency }}</div>
                            <div class="text-xs text-gray-500">{{ $deal->stage->label() }}</div>
                        </div>
                    </div>
                @empty
                    <div class="px-6 py-8 text-center text-sm text-gray-500">No deals yet.</div>
                @endforelse
            </div>
        </div>

        @if ($canViewFeed)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Live activity</h3>
                    <span class="inline-flex items-center gap-1.5 text-xs text-gray-400">
                        <span id="feed-pulse" class="h-2 w-2 rounded-full bg-green-500"></span> live
                    </span>
                </div>
                <ul id="activity-feed" class="divide-y divide-gray-100 max-h-96 overflow-y-auto">
                    <li class="px-6 py-8 text-center text-sm text-gray-400">Loading…</li>
                </ul>
            </div>
        @endif
    </div>

    @if ($canViewFeed)
        <script>
            (function () {
                const list = document.getElementById('activity-feed');
                const pulse = document.getElementById('feed-pulse');
                const endpoint = @json(route('tenant.activity-feed', $tenant));
                let latestId = 0;
                let seeded = false;

                function escapeHtml(str) {
                    const div = document.createElement('div');
                    div.textContent = str == null ? '' : String(str);
                    return div.innerHTML;
                }

                function render(events) {
                    if (! seeded) {
                        list.innerHTML = '';
                        seeded = true;
                    }
                    events.slice().reverse().forEach(function (e) {
                        const li = document.createElement('li');
                        li.className = 'px-6 py-3 text-sm bg-indigo-50/60 transition-colors';
                        li.innerHTML =
                            '<div class="flex items-center justify-between">' +
                            '<span class="font-mono text-xs text-gray-700">' + escapeHtml(e.action) + '</span>' +
                            '<span class="text-xs text-gray-400">' + escapeHtml(e.at) + '</span></div>' +
                            '<div class="text-gray-500 text-xs mt-0.5">' + escapeHtml(e.user) +
                            (e.target ? ' · ' + escapeHtml(e.target) : '') + '</div>';
                        list.prepend(li);
                        setTimeout(function () { li.classList.remove('bg-indigo-50/60'); }, 1500);
                    });
                    while (list.children.length > 30) {
                        list.removeChild(list.lastChild);
                    }
                    if (seeded && list.children.length === 0) {
                        list.innerHTML = '<li class="px-6 py-8 text-center text-sm text-gray-400">No activity yet.</li>';
                    }
                }

                function poll() {
                    fetch(endpoint + '?after=' + latestId, { headers: { 'Accept': 'application/json' } })
                        .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
                        .then(function (data) {
                            if (data.latest_id) latestId = data.latest_id;
                            if (data.events && data.events.length) render(data.events);
                            else if (! seeded) { seeded = true; render([]); }
                            pulse.classList.remove('bg-red-500');
                            pulse.classList.add('bg-green-500');
                        })
                        .catch(function () {
                            pulse.classList.remove('bg-green-500');
                            pulse.classList.add('bg-red-500');
                        });
                }

                poll();
                setInterval(poll, 10000);
            })();
        </script>
    @endif
@endsection

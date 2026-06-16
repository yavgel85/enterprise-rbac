@extends('layouts.app')
@section('title', 'Audit log')
@section('header', 'Audit log')

@section('content')
    <div class="flex flex-wrap items-end justify-between gap-3 mb-6">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Action</label>
                <select name="action" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                    <option value="">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">User</label>
                <select name="user_id" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                    <option value="">All users</option>
                    @foreach ($users as $user)
                        <option value="{{ $user->id }}" @selected((int) request('user_id') === $user->id)>{{ $user->name }} ({{ $user->email }})</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">From</label>
                <input type="date" name="from" value="{{ request('from') }}" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">To</label>
                <input type="date" name="to" value="{{ request('to') }}" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
            </div>
            <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white">Filter</button>
            @if (request()->hasAny(['action', 'user_id', 'from', 'to']))
                <a href="{{ route('admin.audit.index', $tenant) }}" class="px-3 py-2 text-sm text-gray-500 hover:text-gray-700">Reset</a>
            @endif
        </form>

        @if (auth()->user()->hasPermission(App\Enums\Permission::AuditExport))
            <form method="POST" action="{{ route('admin.audit.export', $tenant) }}">
                @csrf
                <button type="submit" class="rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Export CSV</button>
            </form>
        @endif
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700 w-8"></th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">When</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Action</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Target</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">IP</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($logs as $log)
                    @php($hasDiff = ! empty($log->old_values) || ! empty($log->new_values) || ! empty($log->metadata))
                    <tr @class(['hover:bg-gray-50', 'cursor-pointer' => $hasDiff]) @if ($hasDiff) data-audit-toggle="audit-diff-{{ $log->id }}" @endif>
                        <td class="px-4 py-3 text-gray-400">
                            @if ($hasDiff)
                                <span data-audit-caret>&#9656;</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 text-gray-700"><span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200">{{ $log->action }}</span></td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->user?->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—' }}</td>
                        <td class="px-4 py-3 text-gray-500 text-xs">{{ $log->ip_address ?? '—' }}</td>
                    </tr>
                    @if ($hasDiff)
                        <tr id="audit-diff-{{ $log->id }}" hidden>
                            <td></td>
                            <td colspan="5" class="px-4 pb-4 pt-1">
                                @include('admin.audit._diff', ['log' => $log])
                            </td>
                        </tr>
                    @endif
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No audit entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>

    <script>
        document.querySelectorAll('[data-audit-toggle]').forEach(function (row) {
            row.addEventListener('click', function () {
                var target = document.getElementById(row.getAttribute('data-audit-toggle'));
                if (! target) return;
                target.hidden = ! target.hidden;
                var caret = row.querySelector('[data-audit-caret]');
                if (caret) caret.innerHTML = target.hidden ? '&#9656;' : '&#9662;';
            });
        });
    </script>
@endsection

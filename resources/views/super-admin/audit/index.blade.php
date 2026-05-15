@extends('layouts.app')
@section('title', 'Global audit log')
@section('header', 'Global audit log')

@section('content')
    <form method="GET" class="flex items-center gap-2 mb-6">
        <select name="tenant_id" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
            <option value="">All tenants</option>
            @foreach ($tenants as $tenant)
                <option value="{{ $tenant->id }}" @selected(request('tenant_id') == $tenant->id)>{{ $tenant->name }}</option>
            @endforeach
        </select>
        <select name="action" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
            <option value="">All actions</option>
            @foreach ($actions as $action)
                <option value="{{ $action }}" @selected(request('action') === $action)>{{ $action }}</option>
            @endforeach
        </select>
        <button type="submit" class="rounded-md bg-gray-800 px-3 py-2 text-sm font-medium text-white">Filter</button>
    </form>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">When</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Tenant</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Action</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Target</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($logs as $log)
                    <tr>
                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->tenant?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200">{{ $log->action }}</span></td>
                        <td class="px-4 py-3 text-gray-700">{{ $log->user?->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $log->auditable_type ? class_basename($log->auditable_type).'#'.$log->auditable_id : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No audit entries.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $logs->links() }}</div>
@endsection

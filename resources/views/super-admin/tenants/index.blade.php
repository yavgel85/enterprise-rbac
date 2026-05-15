@extends('layouts.app')
@section('title', 'Tenants')
@section('header', 'Tenants')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Platform-wide list of all tenants.</p>
        <a href="{{ route('super-admin.tenants.create') }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New tenant</a>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Slug</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Users</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Roles</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($tenants as $tenant)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900"><a href="{{ route('super-admin.tenants.show', $tenant) }}" class="text-indigo-600 hover:text-indigo-500">{{ $tenant->name }}</a></td>
                        <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $tenant->slug }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $tenant->users_count }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $tenant->roles_count }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1
                                {{ $tenant->is_active ? 'bg-green-50 text-green-700 ring-green-200' : 'bg-red-50 text-red-700 ring-red-200' }}">
                                {{ $tenant->is_active ? 'Active' : 'Suspended' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('super-admin.tenants.show', $tenant) }}" class="text-sm text-indigo-600">Manage</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tenants->links() }}</div>
@endsection

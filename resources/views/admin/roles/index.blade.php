@extends('layouts.app')
@section('title', 'Roles')
@section('header', 'Roles')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Roles available within {{ $tenant->name }}.</p>
        @can('create', App\Models\Role::class)
            <a href="{{ route('admin.roles.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New role</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Slug</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Level</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Users</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Permissions</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $role->name }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $role->slug }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $role->level }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $role->users_count }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $role->permissions_count }}</td>
                        <td class="px-4 py-3 text-right space-x-3">
                            <a href="{{ route('admin.roles.edit', [$tenant, $role]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">Edit</a>
                            @can('create', App\Models\Role::class)
                                <form method="POST" action="{{ route('admin.roles.clone', [$tenant, $role]) }}" class="inline" onsubmit="return confirm('Clone “{{ $role->name }}” into a new editable role?')">
                                    @csrf
                                    <button type="submit" class="text-sm text-emerald-600 hover:text-emerald-700">Clone</button>
                                </form>
                            @endcan
                            @can('delete', $role)
                                <form method="POST" action="{{ route('admin.roles.destroy', [$tenant, $role]) }}" class="inline" onsubmit="return confirm('Delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection

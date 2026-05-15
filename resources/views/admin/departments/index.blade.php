@extends('layouts.app')
@section('title', 'Departments')
@section('header', 'Departments')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Slug</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Users</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @foreach ($departments as $department)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <form method="POST" action="{{ route('admin.departments.update', [$tenant, $department]) }}" class="flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" value="{{ $department->name }}" class="rounded-md border-gray-300 shadow-sm px-2 py-1 border text-sm w-full">
                                    <button type="submit" class="text-xs text-indigo-600">Save</button>
                                </form>
                            </td>
                            <td class="px-4 py-3 text-gray-700 font-mono text-xs">{{ $department->slug }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $department->users_count }}</td>
                            <td class="px-4 py-3 text-right">
                                @can('delete', $department)
                                    <form method="POST" action="{{ route('admin.departments.destroy', [$tenant, $department]) }}" onsubmit="return confirm('Delete this department?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600">Delete</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">New department</h3>
            <form method="POST" action="{{ route('admin.departments.store', $tenant) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block font-medium text-gray-700">Name</label>
                    <input type="text" name="name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create</button>
            </form>
        </div>
    </div>
@endsection

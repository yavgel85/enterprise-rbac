@extends('layouts.app')
@section('title', 'Edit role: '.$role->name)
@section('header', 'Role: '.$role->name)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('admin.roles.update', [$tenant, $role]) }}" class="lg:col-span-1 bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('description', $role->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Level</label>
                <input type="number" name="level" min="0" max="100" value="{{ old('level', $role->level) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
        </form>

        <form method="POST" action="{{ route('admin.roles.permissions.sync', [$tenant, $role]) }}" class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            @csrf
            @method('PUT')
            <h3 class="text-base font-semibold text-gray-900 mb-4">Permissions</h3>
            <div class="space-y-5">
                @foreach ($permissionsByModule as $module => $cases)
                    <div>
                        <div class="text-sm font-semibold text-gray-800 mb-2 uppercase tracking-wide">{{ $module }}</div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($cases as $case)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $case->value }}"
                                        @checked(in_array($case->value, $rolePermissionSlugs, true))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                    <span>{{ $case->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="mt-6 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save permissions</button>
        </form>
    </div>
@endsection

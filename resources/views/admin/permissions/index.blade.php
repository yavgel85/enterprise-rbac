@extends('layouts.app')
@section('title', 'Permissions catalog')
@section('header', 'Permissions catalog')

@section('content')
    <p class="text-sm text-gray-500 mb-4">All available permission slugs, grouped by module. Permissions are managed globally by super-admin.</p>
    <div class="space-y-6">
        @foreach ($permissionsByModule as $module => $permissions)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-3 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">{{ $module }}</h3>
                </div>
                <ul class="divide-y divide-gray-200 text-sm">
                    @foreach ($permissions as $permission)
                        <li class="px-6 py-2 flex items-center justify-between">
                            <span class="font-mono text-gray-800">{{ $permission->slug }}</span>
                            <span class="text-gray-500">{{ $permission->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>
@endsection

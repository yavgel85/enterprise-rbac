@extends('layouts.app')
@section('title', 'Permissions catalog')
@section('header', 'Global permissions catalog')

@section('content')
    <p class="text-sm text-gray-500 mb-4">Read-only catalog. New permissions are added by adding cases to <code>App\Enums\Permission</code> and re-running <code>db:seed --class=PermissionSeeder</code>.</p>
    <div class="space-y-6">
        @foreach ($modules as $module)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-3 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">{{ $module->name }}</h3>
                    <div class="text-xs text-gray-500">{{ $module->slug }} · {{ $module->permissions->count() }} permissions</div>
                </div>
                <ul class="divide-y divide-gray-200 text-sm">
                    @foreach ($module->permissions as $permission)
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

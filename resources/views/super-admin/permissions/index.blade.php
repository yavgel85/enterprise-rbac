@extends('layouts.app')
@section('title', 'Permissions catalog')
@section('header', 'Global permissions catalog')

@section('content')
    <p class="text-sm text-gray-500 mb-1">Read-only catalog. New permissions are added by adding cases to <code>App\Enums\Permission</code> and re-running <code>db:seed --class=PermissionSeeder</code>.</p>
    <p class="text-xs text-gray-400 mb-4">Usage stats are refreshed by <code>php artisan rbac:usage</code> (denials counted over the last {{ $usageWindow }} days). Rows in amber are never granted to any role or user.</p>
    <div class="space-y-6">
        @foreach ($modules as $module)
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
                <div class="px-6 py-3 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">{{ $module->name }}</h3>
                    <div class="text-xs text-gray-500">{{ $module->slug }} · {{ $module->permissions->count() }} permissions</div>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs text-gray-500">
                        <tr>
                            <th class="px-6 py-2 text-left font-medium">Slug</th>
                            <th class="px-4 py-2 text-right font-medium">Roles</th>
                            <th class="px-4 py-2 text-right font-medium">Direct users</th>
                            <th class="px-4 py-2 text-right font-medium">Denied ({{ $usageWindow }}d)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach ($module->permissions as $permission)
                            @php($stat = $usage[$permission->slug] ?? ['granted_roles' => 0, 'granted_users' => 0, 'denied' => 0])
                            @php($unused = $stat['granted_roles'] === 0 && $stat['granted_users'] === 0)
                            <tr class="{{ $unused ? 'bg-amber-50' : '' }}">
                                <td class="px-6 py-2 font-mono text-gray-800">
                                    {{ $permission->slug }}
                                    @if ($permission->is_wildcard)
                                        <span class="ml-2 text-[10px] uppercase tracking-wide text-indigo-600">wildcard</span>
                                    @endif
                                    @if ($unused)
                                        <span class="ml-2 text-[10px] uppercase tracking-wide text-amber-700">unused</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ $stat['granted_roles'] }}</td>
                                <td class="px-4 py-2 text-right text-gray-700">{{ $stat['granted_users'] }}</td>
                                <td class="px-4 py-2 text-right {{ $stat['denied'] > 0 ? 'text-red-600 font-medium' : 'text-gray-400' }}">{{ $stat['denied'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
@endsection

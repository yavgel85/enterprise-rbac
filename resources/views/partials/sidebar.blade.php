@php
    $user = auth()->user();
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
    $tenantPrefix = $tenant ? '/t/'.$tenant->slug : null;
@endphp
<aside class="w-64 bg-gray-900 text-gray-100 flex flex-col">
    <div class="px-6 py-5 border-b border-gray-800">
        <div class="text-xs uppercase tracking-wider text-gray-400">{{ $tenant?->name ?? 'Platform' }}</div>
        <div class="font-semibold truncate">{{ $user->name }}</div>
        <div class="text-xs text-gray-500 truncate">{{ $user->email }}</div>
        @if ($user->is_super_admin)
            <span class="mt-2 inline-flex items-center rounded-md bg-amber-500/10 px-2 py-1 text-xs font-medium text-amber-400 ring-1 ring-amber-500/20">
                Super Admin
            </span>
        @endif
    </div>

    <nav class="flex-1 overflow-y-auto px-3 py-4 space-y-1 text-sm">
        @if ($user->is_super_admin)
            <div class="px-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Platform</div>
            <a href="{{ route('super-admin.tenants.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('super-admin.tenants.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Tenants</a>
            <a href="{{ route('super-admin.permissions.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('super-admin.permissions.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Permissions catalog</a>
            <a href="{{ route('super-admin.audit.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('super-admin.audit.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Global audit</a>
            <a href="{{ route('super-admin.observability.index') }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('super-admin.observability.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Observability</a>
        @endif

        @if ($tenant)
            <div class="px-3 pt-3 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">CRM</div>
            <a href="{{ route('tenant.dashboard', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('tenant.dashboard') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Dashboard</a>
            <a href="{{ route('crm.companies.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.companies.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Companies</a>
            <a href="{{ route('crm.contacts.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.contacts.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Contacts</a>
            <a href="{{ route('crm.deals.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.deals.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Deals</a>
            <a href="{{ route('crm.tasks.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.tasks.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Tasks</a>
            <a href="{{ route('crm.activities.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.activities.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Activities</a>
            @if ($tenant->hasFeature('advanced_analytics') && $user->hasPermission(\App\Enums\Permission::ReportsView))
                <a href="{{ route('crm.reports.analytics', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.reports.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Analytics</a>
            @endif
            @if ($user->hasPermission(\App\Enums\Permission::ApprovalsView))
                @php($pendingApprovals = \App\Models\ApprovalRequest::pendingForUser($user)->count())
                <a href="{{ route('crm.approvals.index', $tenant) }}" class="flex items-center justify-between px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('crm.approvals.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">
                    <span>Approvals</span>
                    @if ($pendingApprovals > 0)
                        <span class="inline-flex items-center justify-center rounded-full bg-amber-500 px-2 text-xs font-semibold text-gray-900">{{ $pendingApprovals }}</span>
                    @endif
                </a>
            @endif

            <div class="px-3 pt-4 pb-1 text-xs font-semibold text-gray-500 uppercase tracking-wider">Administration</div>
            <a href="{{ route('admin.users.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.users.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Users</a>
            <a href="{{ route('admin.roles.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.roles.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Roles</a>
            <a href="{{ route('admin.permissions.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.permissions.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Permissions</a>
            @if ($user->hasPermission(\App\Enums\Permission::PermissionsAssign))
                <a href="{{ route('admin.permission-conditions.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.permission-conditions.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Access conditions</a>
            @endif
            <a href="{{ route('admin.departments.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.departments.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Departments</a>
            <a href="{{ route('admin.audit.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.audit.index') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Audit log</a>
            @if ($user->hasPermission(\App\Enums\Permission::AuditManage))
                <a href="{{ route('admin.audit-sinks.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.audit-sinks.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Audit sinks</a>
            @endif
            @if ($user->hasPermission(\App\Enums\Permission::CustomFieldsManage))
                <a href="{{ route('admin.custom-fields.index', $tenant) }}" class="block px-3 py-2 rounded-md hover:bg-gray-800 {{ request()->routeIs('admin.custom-fields.*') ? 'bg-gray-800 text-white' : 'text-gray-300' }}">Custom fields</a>
            @endif
        @endif
    </nav>

    <div class="px-3 py-3 border-t border-gray-800 space-y-1">
        <a href="{{ route('profile.show') }}"
            class="block w-full text-left px-3 py-2 rounded-md text-sm {{ request()->routeIs('profile.*') ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-800 hover:text-white' }}">
            My profile
        </a>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="w-full text-left px-3 py-2 rounded-md text-sm text-gray-300 hover:bg-gray-800 hover:text-white">
                Sign out
            </button>
        </form>
    </div>
</aside>

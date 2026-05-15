@php
    $tenant = app()->bound('current_tenant') ? app('current_tenant') : null;
@endphp
<header class="bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between">
    <h1 class="text-lg font-semibold text-gray-900">@yield('header', $tenant?->name ?? 'Platform')</h1>
    <div class="text-sm text-gray-500">
        @yield('header-meta')
    </div>
</header>

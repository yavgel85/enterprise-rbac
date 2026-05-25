@extends('layouts.app')
@section('title', $user->name)
@section('header', $user->name)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-sm space-y-3">
                <div><span class="text-gray-500">Email</span><div class="font-medium">{{ $user->email }}</div></div>
                <div><span class="text-gray-500">Department</span><div>{{ $user->department?->name ?? '—' }}</div></div>
                <div><span class="text-gray-500">Status</span><div>{{ $user->is_active ? 'Active' : 'Inactive' }}</div></div>
                <div><span class="text-gray-500">Email verified</span><div>{{ $user->email_verified_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
                <div><span class="text-gray-500">Last login</span><div>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-sm space-y-3">
                <h3 class="text-base font-semibold text-gray-900">Security</h3>
                <div>
                    <span class="text-gray-500">Failed attempts:</span>
                    <span class="font-medium">{{ $user->failed_login_attempts }}</span>
                </div>
                @if ($user->isLocked())
                    <div class="rounded-md bg-red-50 border border-red-200 px-3 py-2 text-red-800">
                        Locked until {{ $user->locked_until->format('Y-m-d H:i') }}
                    </div>
                @endif

                @can('unlock', $user)
                    <form method="POST" action="{{ route('admin.users.unlock', [$tenant, $user]) }}">
                        @csrf
                        @method('PUT')
                        <button type="submit"
                            class="rounded-md bg-amber-600 px-3 py-2 text-xs font-semibold text-white hover:bg-amber-500 disabled:opacity-50"
                            @disabled(! $user->isLocked() && $user->failed_login_attempts === 0)>
                            Unlock / reset attempts
                        </button>
                    </form>
                @endcan
            </div>
        </div>

        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Roles</h3>
                <form method="POST" action="{{ route('admin.users.roles.sync', [$tenant, $user]) }}">
                    @csrf
                    @method('PUT')
                    <div class="space-y-2 text-sm">
                        @foreach ($allRoles as $role)
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                    @checked($user->roles->contains('id', $role->id))
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <span class="font-medium">{{ $role->name }}</span>
                                <span class="text-gray-500">({{ $role->slug }}, level {{ $role->level }})</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit" class="mt-4 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save roles</button>
                </form>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-base font-semibold text-gray-900">Direct permissions</h3>
                    <a href="{{ route('admin.permissions.user.edit', [$tenant, $user]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">Manage</a>
                </div>
                <ul class="space-y-1 text-sm">
                    @forelse ($user->directPermissions as $permission)
                        <li class="flex items-center justify-between">
                            <span>{{ $permission->slug }}</span>
                            <span class="text-xs {{ $permission->pivot->type === 'deny' ? 'text-red-600' : 'text-green-600' }}">{{ $permission->pivot->type }}</span>
                        </li>
                    @empty
                        <li class="text-gray-500">None.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

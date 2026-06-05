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
                            @php($pivot = optional($user->roles->firstWhere('id', $role->id))->pivot)
                            <label class="flex items-center gap-3">
                                <input type="checkbox" name="role_ids[]" value="{{ $role->id }}"
                                    @checked($user->roles->contains('id', $role->id))
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <span class="font-medium">{{ $role->name }}</span>
                                <span class="text-gray-500">({{ $role->slug }}, level {{ $role->level }})</span>
                                @if ($pivot?->expires_at)
                                    <span class="ml-auto text-xs rounded-full bg-amber-100 text-amber-800 px-2 py-0.5">
                                        expires {{ \Illuminate\Support\Carbon::parse($pivot->expires_at)->diffForHumans() }}
                                    </span>
                                @endif
                            </label>
                        @endforeach
                    </div>
                    <p class="mt-2 text-xs text-gray-500">Saving here replaces the full role set with permanent assignments.</p>
                    <button type="submit" class="mt-3 rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save roles</button>
                </form>

                <div class="mt-6 pt-6 border-t border-gray-200">
                    <h4 class="text-sm font-semibold text-gray-900 mb-1">Grant temporary (JIT) role</h4>
                    <p class="text-xs text-gray-500 mb-3">Adds one role that auto-expires, without touching existing assignments.</p>
                    <form method="POST" action="{{ route('admin.users.roles.temporary', [$tenant, $user]) }}" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <select name="role_id" required class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                            @foreach ($allRoles as $role)
                                <option value="{{ $role->id }}">{{ $role->name }} (level {{ $role->level }})</option>
                            @endforeach
                        </select>
                        <div class="flex items-center gap-1 shrink-0">
                            <input type="number" name="hours" min="1" max="8760" value="4" required
                                class="w-20 rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                            <span class="text-sm text-gray-500">hours</span>
                        </div>
                        <button type="submit" class="shrink-0 rounded-md bg-amber-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-amber-500">Grant</button>
                    </form>
                </div>
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

            @can('setPassword', $user)
                <form method="POST" action="{{ route('admin.users.password.update', [$tenant, $user]) }}"
                    class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <h3 class="text-base font-semibold text-gray-900">Set new password</h3>
                        <p class="text-xs text-gray-500 mt-1">All sessions of this user will be terminated.</p>
                    </div>

                    <div>
                        <label for="set_password" class="block text-sm font-medium text-gray-700">New password</label>
                        <input id="set_password" name="password" type="password" autocomplete="new-password" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                        @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="set_password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                        <input id="set_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                    </div>

                    <button type="submit"
                        class="rounded-md bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-500"
                        onclick="return confirm('Force a new password for {{ $user->email }}?')">
                        Set password
                    </button>
                </form>
            @endcan
        </div>
    </div>
@endsection

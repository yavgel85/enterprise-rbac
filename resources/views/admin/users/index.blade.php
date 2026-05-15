@extends('layouts.app')
@section('title', 'Users')
@section('header', 'Users')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-900">Active users</h3>
            </div>
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Email</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Department</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-700">Roles</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse ($users as $user)
                        <tr>
                            <td class="px-4 py-3"><a href="{{ route('admin.users.show', [$tenant, $user]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $user->name }}</a></td>
                            <td class="px-4 py-3 text-gray-700">{{ $user->email }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $user->department?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">
                                @forelse ($user->roles as $role)
                                    <span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-200 mr-1">{{ $role->slug }}</span>
                                @empty
                                    —
                                @endforelse
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.users.show', [$tenant, $user]) }}" class="text-sm text-indigo-600">Manage</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No users.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-3">{{ $users->links() }}</div>
        </div>

        <div class="space-y-6">
            @can('invite', App\Models\User::class)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">Invite user</h3>
                    <form method="POST" action="{{ route('admin.users.invite', $tenant) }}" class="space-y-3 text-sm">
                        @csrf
                        <div>
                            <label class="block font-medium text-gray-700">Email</label>
                            <input type="email" name="email" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                            @error('email') <p class="text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block font-medium text-gray-700">Role</label>
                            <select name="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                <option value="">— none —</option>
                                @foreach (App\Models\Role::where('tenant_id', $tenant->id)->orderBy('level', 'desc')->get() as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }} (lvl {{ $role->level }})</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Send invite</button>
                    </form>
                </div>
            @endcan

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Pending invitations</h3>
                <ul class="space-y-2 text-sm">
                    @forelse ($invitations as $invitation)
                        <li class="flex items-center justify-between">
                            <div>
                                <div class="font-medium">{{ $invitation->email }}</div>
                                <div class="text-xs text-gray-500">expires {{ $invitation->expires_at->diffForHumans() }}</div>
                            </div>
                            <a href="{{ route('invitation.show', $invitation->token) }}" class="text-xs text-indigo-600">Open</a>
                        </li>
                    @empty
                        <li class="text-gray-500">No pending invitations.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection

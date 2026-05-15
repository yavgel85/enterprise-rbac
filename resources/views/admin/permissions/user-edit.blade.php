@extends('layouts.app')
@section('title', 'Direct permissions: '.$user->name)
@section('header', 'Direct permissions for '.$user->name)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-4">Current direct grants/denies</h3>
            @if ($user->directPermissions->isEmpty())
                <p class="text-sm text-gray-500">No direct permissions assigned. The user inherits permissions from their roles only.</p>
            @else
                <ul class="divide-y divide-gray-200">
                    @foreach ($user->directPermissions as $permission)
                        <li class="py-3 flex items-center justify-between text-sm">
                            <div>
                                <div class="font-mono">{{ $permission->slug }}</div>
                                @if ($permission->pivot->reason)
                                    <div class="text-xs text-gray-500">Reason: {{ $permission->pivot->reason }}</div>
                                @endif
                                @if ($permission->pivot->expires_at)
                                    <div class="text-xs text-gray-500">Expires: {{ $permission->pivot->expires_at }}</div>
                                @endif
                            </div>
                            <div class="flex items-center gap-3">
                                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1
                                    {{ $permission->pivot->type === 'deny' ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-green-50 text-green-700 ring-green-200' }}">
                                    {{ $permission->pivot->type }}
                                </span>
                                <form method="POST" action="{{ route('admin.permissions.user.revoke', [$tenant, $user, $permission]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-700">Revoke</button>
                                </form>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <form method="POST" action="{{ route('admin.permissions.user.grant', [$tenant, $user]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf
            <h3 class="text-base font-semibold text-gray-900">Add direct permission</h3>
            <div>
                <label class="block text-sm font-medium text-gray-700">Permission</label>
                <select name="permission_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    @foreach ($allPermissions as $p)
                        <option value="{{ $p->id }}">{{ $p->slug }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    <option value="grant">Grant</option>
                    <option value="deny">Deny</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Expires at (optional)</label>
                <input type="date" name="expires_at" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Reason</label>
                <input type="text" name="reason" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
        </form>
    </div>
@endsection

@extends('layouts.app')
@section('title', 'Access conditions (ABAC)')
@section('header', 'Access conditions (ABAC)')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-base font-semibold text-gray-900">Active conditions</h3>
                    <p class="text-xs text-gray-500">Evaluated <strong>after</strong> a permission is granted. All applicable conditions must hold, otherwise access is denied.</p>
                </div>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Permission</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Role scope</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Condition</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($conditions as $condition)
                            <tr>
                                <td class="px-4 py-3 font-mono text-xs text-gray-900">{{ $condition->permission->slug }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ $condition->role?->name ?? 'All roles' }}{{ $condition->tenant_id === null ? ' · global' : '' }}</td>
                                <td class="px-4 py-3">
                                    @if ($condition->description)
                                        <div class="text-gray-700">{{ $condition->description }}</div>
                                    @endif
                                    <pre class="mt-1 text-xs bg-gray-50 rounded p-2 overflow-x-auto">{{ json_encode($condition->conditions, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </td>
                                <td class="px-4 py-3 text-right align-top">
                                    @if ($condition->tenant_id === $tenant->id)
                                        <form method="POST" action="{{ route('admin.permission-conditions.destroy', [$tenant, $condition]) }}" onsubmit="return confirm('Remove this condition?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs text-red-600">Delete</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">global</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No conditions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-sm text-gray-600">
                <h3 class="text-base font-semibold text-gray-900 mb-2">DSL cheatsheet</h3>
                <ul class="list-disc pl-5 space-y-1">
                    <li>Groups: <code>{"all": [...]}</code>, <code>{"any": [...]}</code>, <code>{"not": {...}}</code></li>
                    <li>Leaf: <code>{"attr": "deal.status", "op": "=", "value": "active"}</code></li>
                    <li>Reference current user via <code>$</code>: <code>{"attr": "deal.owner_id", "op": "=", "value": "$user.id"}</code></li>
                    <li>Operators: <code>= != &gt; &lt; &gt;= &lt;= in not_in contains</code></li>
                </ul>
            </div>
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">New condition</h3>
            @if ($errors->any())
                <div class="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">
                    {{ $errors->first() }}
                </div>
            @endif
            <form method="POST" action="{{ route('admin.permission-conditions.store', $tenant) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block font-medium text-gray-700">Permission</label>
                    <select name="permission_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        @foreach ($permissions as $permission)
                            <option value="{{ $permission->id }}">{{ $permission->slug }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Role scope (optional)</label>
                    <select name="role_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        <option value="">All roles</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Conditions (JSON)</label>
                    <textarea name="conditions" rows="7" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border font-mono text-xs" placeholder='{"attr": "deal.owner_id", "op": "=", "value": "$user.id"}'>{{ old('conditions') }}</textarea>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Description (optional)</label>
                    <input type="text" name="description" value="{{ old('description') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                </div>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Add condition</button>
            </form>
        </div>
    </div>
@endsection

@extends('layouts.app')
@section('title', 'Edit role: '.$role->name)
@section('header', 'Role: '.$role->name)

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('admin.roles.update', [$tenant, $role]) }}" class="lg:col-span-1 bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700">Name</label>
                <input type="text" name="name" value="{{ old('name', $role->name) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Description</label>
                <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('description', $role->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Level</label>
                <input type="number" name="level" min="0" max="100" value="{{ old('level', $role->level) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
        </form>

        <form method="POST" action="{{ route('admin.roles.parent.sync', [$tenant, $role]) }}" class="lg:col-span-1 lg:col-start-1 bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
            @csrf
            @method('PUT')
            <div>
                <h3 class="text-base font-semibold text-gray-900">Inheritance</h3>
                <p class="text-xs text-gray-500 mt-1">This role also receives every permission of its parent (and the parent's parent, recursively).</p>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Inherits from</label>
                <select name="parent_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    <option value="">— none —</option>
                    @foreach ($parentCandidates as $candidate)
                        <option value="{{ $candidate->id }}" @selected($role->parent_id === $candidate->id)>
                            {{ $candidate->name }} (level {{ $candidate->level }})
                        </option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="w-full rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save inheritance</button>
        </form>

        <div class="lg:col-span-1 lg:col-start-1 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900">Impact</h3>
            <p class="text-xs text-gray-500 mt-1">Saving permissions immediately recomputes access for everyone holding this role.</p>
            <p class="mt-3 text-sm"><span class="text-2xl font-semibold text-gray-900">{{ $affectedUserCount }}</span> <span class="text-gray-500">user(s) affected</span></p>
            @if ($affectedUsers->isNotEmpty())
                <ul class="mt-3 space-y-1 text-sm max-h-48 overflow-auto">
                    @foreach ($affectedUsers as $member)
                        <li class="text-gray-700">{{ $member->name }} <span class="text-gray-400">· {{ $member->email }}</span></li>
                    @endforeach
                </ul>
                @if ($affectedUserCount > $affectedUsers->count())
                    <p class="mt-2 text-xs text-gray-400">…and {{ $affectedUserCount - $affectedUsers->count() }} more.</p>
                @endif
            @endif
        </div>

        <div class="lg:col-span-2 space-y-6">
        @if (session('perm_diff') && (session('perm_diff')['added'] || session('perm_diff')['removed']))
            <div class="rounded-lg border border-gray-200 bg-white shadow-sm p-6">
                <h3 class="text-base font-semibold text-gray-900 mb-3">Last change</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <div class="font-medium text-green-700 mb-1">Added ({{ count(session('perm_diff')['added']) }})</div>
                        @forelse (session('perm_diff')['added'] as $slug)
                            <div class="font-mono text-xs text-green-800">+ {{ $slug }}</div>
                        @empty
                            <div class="text-gray-400 text-xs">none</div>
                        @endforelse
                    </div>
                    <div>
                        <div class="font-medium text-red-700 mb-1">Removed ({{ count(session('perm_diff')['removed']) }})</div>
                        @forelse (session('perm_diff')['removed'] as $slug)
                            <div class="font-mono text-xs text-red-800">− {{ $slug }}</div>
                        @empty
                            <div class="text-gray-400 text-xs">none</div>
                        @endforelse
                    </div>
                </div>
            </div>
        @endif

        @if ($permissionGroups->isNotEmpty())
            <form method="POST" action="{{ route('admin.roles.groups.apply', [$tenant, $role]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                @csrf
                <h3 class="text-base font-semibold text-gray-900 mb-1">Apply a permission bundle</h3>
                <p class="text-xs text-gray-500 mb-3">Adds every permission in the bundle on top of the current set (nothing is removed).</p>
                <div class="flex flex-col sm:flex-row gap-3">
                    <select name="permission_group_id" required class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                        @foreach ($permissionGroups as $group)
                            <option value="{{ $group->id }}">{{ $group->name }} ({{ $group->permissions_count }} perms)</option>
                        @endforeach
                    </select>
                    <button type="submit" class="shrink-0 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Apply bundle</button>
                </div>
            </form>
        @endif

        <form method="POST" action="{{ route('admin.roles.permissions.sync', [$tenant, $role]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6" data-perm-form>
            @csrf
            @method('PUT')
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Permissions</h3>
                <span data-perm-diff class="text-xs font-medium text-gray-400">No unsaved changes</span>
            </div>
            <div class="space-y-5">
                @foreach ($permissionsByModule as $module => $cases)
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <div class="text-sm font-semibold text-gray-800 uppercase tracking-wide">{{ $module }}</div>
                            <label class="flex items-center gap-2 text-xs text-indigo-700">
                                <input type="checkbox" name="permissions[]" value="{{ $module }}.*"
                                    @checked(in_array($module.'.*', $rolePermissionSlugs, true))
                                    class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                <span class="font-mono">{{ $module }}.* (grant all)</span>
                            </label>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                            @foreach ($cases as $case)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="permissions[]" value="{{ $case->value }}"
                                        @checked(in_array($case->value, $rolePermissionSlugs, true))
                                        class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                                    <span>{{ $case->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
            <button type="submit" class="mt-6 rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save permissions</button>
        </form>
        </div>
    </div>

    <script>
        (function () {
            const form = document.querySelector('[data-perm-form]');
            if (!form) return;
            const label = form.querySelector('[data-perm-diff]');
            const boxes = Array.from(form.querySelectorAll('input[name="permissions[]"]'));
            const initial = new Map(boxes.map(b => [b.value, b.checked]));

            function update() {
                let added = 0, removed = 0;
                boxes.forEach(b => {
                    if (b.checked && !initial.get(b.value)) added++;
                    if (!b.checked && initial.get(b.value)) removed++;
                });
                if (added === 0 && removed === 0) {
                    label.textContent = 'No unsaved changes';
                    label.className = 'text-xs font-medium text-gray-400';
                } else {
                    label.textContent = `Unsaved: +${added} / −${removed}`;
                    label.className = 'text-xs font-semibold text-amber-600';
                }
            }

            boxes.forEach(b => b.addEventListener('change', update));
        })();
    </script>
@endsection

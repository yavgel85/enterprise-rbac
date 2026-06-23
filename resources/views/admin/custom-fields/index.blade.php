@extends('layouts.app')
@section('title', 'Custom fields')
@section('header', 'Custom fields')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 text-sm text-gray-600">
                <p>Define tenant-specific fields for CRM records. They appear on create/edit forms and the record detail page.</p>
            </div>

            @forelse ($definitions as $modelType => $group)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <h3 class="text-base font-semibold text-gray-900 mb-3">{{ class_basename($modelType) }}</h3>
                    <div class="space-y-3">
                        @foreach ($group as $definition)
                            <div class="border border-gray-100 rounded-md p-3">
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-xs text-gray-400">
                                        <code>{{ $definition->key }}</code> · {{ $definition->type->label() }}
                                    </div>
                                    <form method="POST" action="{{ route('admin.custom-fields.destroy', [$tenant, $definition]) }}" onsubmit="return confirm('Delete this field and all its values?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs text-red-600">Delete</button>
                                    </form>
                                </div>
                                <form method="POST" action="{{ route('admin.custom-fields.update', [$tenant, $definition]) }}"
                                    class="grid grid-cols-1 sm:grid-cols-6 gap-2 items-center text-sm">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="label" value="{{ $definition->label }}" required title="Label"
                                        class="sm:col-span-2 block w-full rounded-md border-gray-300 shadow-sm px-2 py-1.5 border">
                                    <input type="number" name="position" value="{{ $definition->position }}" title="Position"
                                        class="rounded-md border-gray-300 shadow-sm px-2 py-1.5 border">
                                    @if ($definition->type->usesOptions())
                                        <textarea name="options" rows="1" placeholder="One per line"
                                            class="sm:col-span-1 rounded-md border-gray-300 shadow-sm px-2 py-1.5 border">{{ implode("\n", $definition->options ?? []) }}</textarea>
                                    @else
                                        <span class="text-xs text-gray-400 text-center">—</span>
                                    @endif
                                    <label class="inline-flex items-center gap-1 text-xs"><input type="checkbox" name="required" value="1" @checked($definition->required)> req</label>
                                    <button type="submit" class="text-xs font-semibold text-indigo-600 text-right">Save</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm px-5 py-10 text-center text-sm text-gray-500">No custom fields defined yet.</div>
            @endforelse
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">New custom field</h3>
            @if ($errors->any())
                <div class="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{{ $errors->first() }}</div>
            @endif
            @if (session('error'))
                <div class="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{{ session('error') }}</div>
            @endif
            <form method="POST" action="{{ route('admin.custom-fields.store', $tenant) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block font-medium text-gray-700">Model</label>
                    <select name="model" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        @foreach ($models as $key => $label)
                            <option value="{{ $key }}" @selected(old('model') === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Label</label>
                    <input type="text" name="label" value="{{ old('label') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Key</label>
                    <input type="text" name="key" value="{{ old('key') }}" required placeholder="e.g. industry_code"
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    <p class="mt-1 text-xs text-gray-400">Lowercase letters, digits and underscores. Cannot be changed later.</p>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Type</label>
                    <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Options (select only)</label>
                    <textarea name="options" rows="3" placeholder="One option per line" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('options') }}</textarea>
                </div>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="required" value="1" @checked(old('required'))> Required</label>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create field</button>
            </form>
        </div>
    </div>
@endsection

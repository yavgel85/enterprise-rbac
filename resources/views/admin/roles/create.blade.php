@extends('layouts.app')
@section('title', 'New role')
@section('header', 'New role')

@section('content')
    @if ($cloneable->isNotEmpty())
        <form method="POST" action="{{ route('admin.roles.clone', [$tenant, '__placeholder__']) }}" id="clone-form" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 mb-6 max-w-xl"
            onsubmit="this.action = this.action.replace('__placeholder__', document.getElementById('clone-source').value)">
            @csrf
            <h3 class="text-base font-semibold text-gray-900 mb-1">Clone an existing role</h3>
            <p class="text-xs text-gray-500 mb-3">Creates a new editable role (one level below the source) with the same permissions.</p>
            <div class="flex flex-col sm:flex-row gap-3">
                <select id="clone-source" class="block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm">
                    @foreach ($cloneable as $source)
                        <option value="{{ $source->id }}">{{ $source->name }} (level {{ $source->level }})</option>
                    @endforeach
                </select>
                <button type="submit" class="shrink-0 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-500">Clone</button>
            </div>
        </form>
    @endif

    <form method="POST" action="{{ route('admin.roles.store', $tenant) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4 max-w-xl">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Slug (optional)</label>
            <input type="text" name="slug" value="{{ old('slug') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Description</label>
            <textarea name="description" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('description') }}</textarea>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Level (0-100)</label>
            <input type="number" name="level" min="0" max="100" value="{{ old('level', 10) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <p class="text-xs text-gray-500 mt-1">You cannot create a role with a level greater than or equal to your own.</p>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.roles.index', $tenant) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create</button>
        </div>
    </form>
@endsection

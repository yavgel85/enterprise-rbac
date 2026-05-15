@extends('layouts.app')
@section('title', 'New tenant')
@section('header', 'New tenant')

@section('content')
    <form method="POST" action="{{ route('super-admin.tenants.store') }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4 max-w-xl">
        @csrf
        <div>
            <label class="block text-sm font-medium text-gray-700">Name</label>
            <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @error('name') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700">Slug</label>
            <input type="text" name="slug" value="{{ old('slug') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <p class="text-xs text-gray-500 mt-1">Used in URLs like <code>/t/{slug}/...</code></p>
            @error('slug') <p class="text-red-600 text-sm mt-1">{{ $message }}</p> @enderror
        </div>
        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="is_active" value="1" checked class="h-4 w-4 rounded border-gray-300 text-indigo-600">
            <span>Active immediately</span>
        </label>
        <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create and bootstrap</button>
    </form>
@endsection

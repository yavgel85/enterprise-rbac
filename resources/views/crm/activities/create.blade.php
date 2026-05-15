@extends('layouts.app')
@section('title', 'New activity')
@section('header', 'New activity')

@section('content')
    <form method="POST" action="{{ route('crm.activities.store', $tenant) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Type</label>
                <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    @foreach (App\Enums\ActivityType::cases() as $case)
                        <option value="{{ $case->value }}">{{ $case->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700">Happened at</label>
                <input type="datetime-local" name="happened_at" value="{{ old('happened_at', now()->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Subject</label>
                <input type="text" name="subject" value="{{ old('subject') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Body</label>
                <textarea name="body" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('body') }}</textarea>
            </div>
        </div>
        <div class="flex justify-end gap-3">
            <a href="{{ route('crm.activities.index', $tenant) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
        </div>
    </form>
@endsection

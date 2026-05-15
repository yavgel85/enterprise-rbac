@extends('layouts.app')
@section('title', 'Edit task')
@section('header', $task->title)
@section('content')
    <form method="POST" action="{{ route('crm.tasks.update', [$tenant, $task]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')
        @include('crm.tasks._form')
        <div class="flex justify-between items-center">
            @can('delete', $task)
                <form method="POST" action="{{ route('crm.tasks.destroy', [$tenant, $task]) }}" onsubmit="return confirm('Delete this task?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
                </form>
            @endcan
            <div class="flex gap-3">
                <a href="{{ route('crm.tasks.show', [$tenant, $task]) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </div>
    </form>
@endsection

@extends('layouts.app')
@section('title', $task->title)
@section('header', $task->title)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Status: <strong>{{ $task->status->label() }}</strong> · Priority: <strong>{{ $task->priority->label() }}</strong></p>
        <div class="flex gap-2">
            @can('complete', $task)
                @if ($task->status !== App\Enums\TaskStatus::Done)
                    <form method="POST" action="{{ route('crm.tasks.complete', [$tenant, $task]) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-green-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-green-500">Mark complete</button>
                    </form>
                @endif
            @endcan
            @can('update', $task)
                <a href="{{ route('crm.tasks.edit', [$tenant, $task]) }}" class="rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
        <div class="flex"><span class="w-32 text-gray-500">Assignee</span><span>{{ $task->assignee?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-32 text-gray-500">Due date</span><span>{{ $task->due_date?->format('Y-m-d H:i') ?? '—' }}</span></div>
        <div class="flex"><span class="w-32 text-gray-500">Completed</span><span>{{ $task->completed_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
        <div><span class="text-gray-500 block mb-1">Description</span><div class="whitespace-pre-wrap">{{ $task->description ?? '—' }}</div></div>
    </div>
@endsection

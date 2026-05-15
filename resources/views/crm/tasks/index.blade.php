@extends('layouts.app')
@section('title', 'Tasks')
@section('header', 'Tasks')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Tasks for {{ $tenant->name }}.</p>
        @can('create', App\Models\Task::class)
            <a href="{{ route('crm.tasks.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New task</a>
        @endcan
    </div>
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Assignee</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Priority</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Due</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($tasks as $task)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('crm.tasks.show', [$tenant, $task]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $task->title }}</a></td>
                        <td class="px-4 py-3 text-gray-700">{{ $task->assignee?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $task->status->label() }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $task->priority->label() }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $task->due_date?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No tasks yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $tasks->links() }}</div>
@endsection

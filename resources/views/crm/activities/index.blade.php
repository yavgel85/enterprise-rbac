@extends('layouts.app')
@section('title', 'Activities')
@section('header', 'Activities')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Calls, meetings, emails and notes.</p>
        @can('create', App\Models\Activity::class)
            <a href="{{ route('crm.activities.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New activity</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Type</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Subject</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">User</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Happened at</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($activities as $activity)
                    <tr>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-1 text-xs font-medium text-purple-700 ring-1 ring-purple-200">{{ $activity->type->label() }}</span></td>
                        <td class="px-4 py-3 text-gray-700">{{ $activity->subject }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $activity->user?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $activity->happened_at?->format('Y-m-d H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-gray-500">No activities yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $activities->links() }}</div>
@endsection

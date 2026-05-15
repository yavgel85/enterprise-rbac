@extends('layouts.app')

@section('title', 'Deals')
@section('header', 'Deals')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Sales pipeline for {{ $tenant->name }}.</p>
        @can('create', App\Models\Deal::class)
            <a href="{{ route('crm.deals.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New deal</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Title</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Company</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Stage</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Amount</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Owner</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($deals as $deal)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('crm.deals.show', [$tenant, $deal]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $deal->title }}</a></td>
                        <td class="px-4 py-3 text-gray-700">{{ $deal->company?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-blue-200">{{ $deal->stage->label() }}</span></td>
                        <td class="px-4 py-3 text-gray-700">{{ $deal->status->label() }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ number_format((float) $deal->amount, 2) }} {{ $deal->currency }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $deal->owner?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-gray-500">No deals yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $deals->links() }}</div>
@endsection

@extends('layouts.app')

@section('title', $tenant->name.' dashboard')
@section('header', $tenant->name)

@section('content')
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
        @foreach (['Users' => $stats['users'], 'Companies' => $stats['companies'], 'Contacts' => $stats['contacts'], 'Deals' => $stats['deals'], 'Activities' => $stats['activities']] as $label => $value)
            <div class="bg-white rounded-lg border border-gray-200 px-5 py-4 shadow-sm">
                <div class="text-xs uppercase tracking-wider text-gray-500">{{ $label }}</div>
                <div class="text-2xl font-semibold text-gray-900 mt-1">{{ $value }}</div>
            </div>
        @endforeach
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="text-base font-semibold text-gray-900">Recent deals</h3>
            <a href="{{ route('crm.deals.index', $tenant) }}" class="text-sm text-indigo-600 hover:text-indigo-500">View all</a>
        </div>
        <div class="divide-y divide-gray-200">
            @forelse ($recentDeals as $deal)
                <div class="px-6 py-3 flex items-center justify-between text-sm">
                    <div>
                        <div class="font-medium text-gray-900">{{ $deal->title }}</div>
                        <div class="text-gray-500">{{ $deal->company?->name ?? '—' }} · owner {{ $deal->owner?->name ?? '—' }}</div>
                    </div>
                    <div class="text-right">
                        <div class="font-medium">{{ number_format((float) $deal->amount, 2) }} {{ $deal->currency }}</div>
                        <div class="text-xs text-gray-500">{{ $deal->stage->label() }}</div>
                    </div>
                </div>
            @empty
                <div class="px-6 py-8 text-center text-sm text-gray-500">No deals yet.</div>
            @endforelse
        </div>
    </div>
@endsection

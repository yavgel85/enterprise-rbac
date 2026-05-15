@extends('layouts.app')
@section('title', $deal->title)
@section('header', $deal->title)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">
            Owner: <strong>{{ $deal->owner?->name ?? 'unassigned' }}</strong> ·
            Stage: <strong>{{ $deal->stage->label() }}</strong> ·
            Status: <strong>{{ $deal->status->label() }}</strong>
        </p>
        <div class="flex gap-2">
            @can('approve', $deal)
                <form method="POST" action="{{ route('crm.deals.approve', [$tenant, $deal]) }}">
                    @csrf
                    <button type="submit" class="rounded-md bg-green-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-green-500">Approve & close</button>
                </form>
            @endcan
            @can('update', $deal)
                <a href="{{ route('crm.deals.edit', [$tenant, $deal]) }}" class="rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
        <div class="flex"><span class="w-40 text-gray-500">Amount</span><span>{{ number_format((float) $deal->amount, 2) }} {{ $deal->currency }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Probability</span><span>{{ $deal->probability }}%</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Company</span><span>{{ $deal->company?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Contact</span><span>{{ $deal->contact?->fullName() ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Department</span><span>{{ $deal->department?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Expected close</span><span>{{ $deal->expected_close_date?->format('Y-m-d') ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Closed at</span><span>{{ $deal->closed_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
    </div>
@endsection

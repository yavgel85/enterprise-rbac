@extends('layouts.app')

@section('title', $contact->fullName())
@section('header', $contact->fullName())

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Owner: <strong>{{ $contact->owner?->name ?? 'unassigned' }}</strong></p>
        @can('update', $contact)
            <a href="{{ route('crm.contacts.edit', [$tenant, $contact]) }}" class="rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
        <div class="flex"><span class="w-32 text-gray-500">Company</span><span>{{ $contact->company?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-32 text-gray-500">Position</span><span>{{ $contact->position ?? '—' }}</span></div>
        <div class="flex"><span class="w-32 text-gray-500">Email</span><span>{{ $contact->email ?? '—' }}</span></div>
        <div class="flex"><span class="w-32 text-gray-500">Phone</span><span>{{ $contact->phone ?? '—' }}</span></div>
    </div>
@endsection

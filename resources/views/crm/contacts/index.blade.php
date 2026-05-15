@extends('layouts.app')

@section('title', 'Contacts')
@section('header', 'Contacts')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">All contacts in {{ $tenant->name }}.</p>
        @can('create', App\Models\Contact::class)
            <a href="{{ route('crm.contacts.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New contact</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Company</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Position</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Email</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Owner</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($contacts as $contact)
                    <tr>
                        <td class="px-4 py-3"><a href="{{ route('crm.contacts.show', [$tenant, $contact]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $contact->fullName() }}</a></td>
                        <td class="px-4 py-3 text-gray-700">{{ $contact->company?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $contact->position ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $contact->email ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $contact->owner?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No contacts yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $contacts->links() }}</div>
@endsection

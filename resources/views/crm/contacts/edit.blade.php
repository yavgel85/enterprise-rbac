@extends('layouts.app')

@section('title', 'Edit contact')
@section('header', $contact->fullName())

@section('content')
    <form method="POST" action="{{ route('crm.contacts.update', [$tenant, $contact]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')
        @include('crm.contacts._form')
        <div class="flex justify-between items-center">
            @can('delete', $contact)
                <form method="POST" action="{{ route('crm.contacts.destroy', [$tenant, $contact]) }}" onsubmit="return confirm('Delete this contact?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
                </form>
            @endcan
            <div class="flex gap-3">
                <a href="{{ route('crm.contacts.show', [$tenant, $contact]) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </div>
    </form>
@endsection

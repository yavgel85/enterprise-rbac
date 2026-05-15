@extends('layouts.app')

@section('title', 'New contact')
@section('header', 'New contact')

@section('content')
    <form method="POST" action="{{ route('crm.contacts.store', $tenant) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @include('crm.contacts._form', ['contact' => null])
        <div class="flex justify-end gap-3">
            <a href="{{ route('crm.contacts.index', $tenant) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create</button>
        </div>
    </form>
@endsection

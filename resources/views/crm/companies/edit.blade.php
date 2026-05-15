@extends('layouts.app')

@section('title', 'Edit company')
@section('header', $company->name)

@section('content')
    <form method="POST" action="{{ route('crm.companies.update', [$tenant, $company]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')
        @include('crm.companies._form')

        <div class="flex justify-between items-center">
            @can('delete', $company)
                <form method="POST" action="{{ route('crm.companies.destroy', [$tenant, $company]) }}" onsubmit="return confirm('Delete this company?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
                </form>
            @endcan

            <div class="flex gap-3">
                <a href="{{ route('crm.companies.show', [$tenant, $company]) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </div>
    </form>
@endsection

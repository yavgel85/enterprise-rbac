@extends('layouts.app')
@section('title', 'Edit deal')
@section('header', $deal->title)

@section('content')
    <form method="POST" action="{{ route('crm.deals.update', [$tenant, $deal]) }}" class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-6">
        @csrf
        @method('PUT')
        @include('crm.deals._form')
        <div class="flex justify-between items-center">
            @can('delete', $deal)
                <form method="POST" action="{{ route('crm.deals.destroy', [$tenant, $deal]) }}" onsubmit="return confirm('Delete this deal?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="text-sm text-red-600 hover:text-red-700">Delete</button>
                </form>
            @endcan
            <div class="flex gap-3">
                <a href="{{ route('crm.deals.show', [$tenant, $deal]) }}" class="px-4 py-2 text-sm text-gray-600">Cancel</a>
                <button type="submit" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Save</button>
            </div>
        </div>
    </form>
@endsection

@extends('layouts.app')

@section('title', 'Companies')
@section('header', 'Companies')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Manage all companies in the {{ $tenant->name }} tenant.</p>
        @can('create', App\Models\Company::class)
            <a href="{{ route('crm.companies.create', $tenant) }}" class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">New company</a>
        @endcan
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Name</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Industry</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Owner</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($companies as $company)
                    <tr>
                        <td class="px-4 py-3">
                            <a href="{{ route('crm.companies.show', [$tenant, $company]) }}" class="font-medium text-indigo-600 hover:text-indigo-500">{{ $company->name }}</a>
                        </td>
                        <td class="px-4 py-3 text-gray-700">{{ $company->industry ?? '—' }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $company->owner?->name ?? '—' }}</td>
                        <td class="px-4 py-3"><span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-gray-200">{{ $company->status->label() }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('crm.companies.show', [$tenant, $company]) }}" class="text-sm text-indigo-600 hover:text-indigo-500">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No companies yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $companies->links() }}</div>
@endsection

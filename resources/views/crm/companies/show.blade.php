@extends('layouts.app')

@section('title', $company->name)
@section('header', $company->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Owner: <strong>{{ $company->owner?->name ?? 'unassigned' }}</strong> · Status: <strong>{{ $company->status->label() }}</strong></p>
        @can('update', $company)
            <a href="{{ route('crm.companies.edit', [$tenant, $company]) }}" class="rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
        @endcan
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
            <div class="flex"><span class="w-32 text-gray-500">Industry</span><span>{{ $company->industry ?? '—' }}</span></div>
            <div class="flex"><span class="w-32 text-gray-500">Email</span><span>{{ $company->email ?? '—' }}</span></div>
            <div class="flex"><span class="w-32 text-gray-500">Phone</span><span>{{ $company->phone ?? '—' }}</span></div>
            <div class="flex"><span class="w-32 text-gray-500">Website</span><span>{{ $company->website ?? '—' }}</span></div>
            <div class="flex"><span class="w-32 text-gray-500">Address</span><span class="whitespace-pre-wrap">{{ $company->address ?? '—' }}</span></div>
            <div class="flex"><span class="w-32 text-gray-500">Notes</span><span class="whitespace-pre-wrap">{{ $company->notes ?? '—' }}</span></div>
        </div>
        <div class="space-y-4">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="text-sm font-semibold text-gray-900 mb-3">Contacts ({{ $company->contacts->count() }})</div>
                <ul class="space-y-1 text-sm">
                    @forelse ($company->contacts as $contact)
                        <li><a href="{{ route('crm.contacts.show', [$tenant, $contact]) }}" class="text-indigo-600 hover:text-indigo-500">{{ $contact->fullName() }}</a></li>
                    @empty
                        <li class="text-gray-500">No contacts.</li>
                    @endforelse
                </ul>
            </div>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
                <div class="text-sm font-semibold text-gray-900 mb-3">Deals ({{ $company->deals->count() }})</div>
                <ul class="space-y-1 text-sm">
                    @forelse ($company->deals as $deal)
                        <li><a href="{{ route('crm.deals.show', [$tenant, $deal]) }}" class="text-indigo-600 hover:text-indigo-500">{{ $deal->title }}</a> · {{ $deal->stage->label() }}</li>
                    @empty
                        <li class="text-gray-500">No deals.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    @include('crm._custom-fields-show', ['model' => $company, 'modelType' => \App\Models\Company::class])

    @include('crm._attachments', ['attachable' => $company, 'attachableType' => 'company', 'tenant' => $tenant])
@endsection

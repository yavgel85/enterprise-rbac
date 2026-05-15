@extends('layouts.app')
@section('title', $tenant->name)
@section('header', $tenant->name)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">Slug <code>{{ $tenant->slug }}</code> · {{ $tenant->is_active ? 'Active' : 'Suspended' }}</p>
        <form method="POST" action="{{ route('super-admin.tenants.toggle', $tenant) }}">
            @csrf
            @method('PUT')
            <button type="submit" class="rounded-md bg-gray-800 px-3.5 py-2 text-sm font-semibold text-white">
                {{ $tenant->is_active ? 'Suspend' : 'Activate' }}
            </button>
        </form>
    </div>

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Features</h3>
        <div class="space-y-3">
            @foreach ($allFeatures as $feature)
                @php($enabled = $tenant->features->firstWhere('id', $feature->id)?->pivot?->is_enabled)
                <form method="POST" action="{{ route('super-admin.tenants.features.toggle', [$tenant, $feature]) }}" class="flex items-center justify-between text-sm">
                    @csrf
                    @method('PUT')
                    <div>
                        <div class="font-medium">{{ $feature->name }}</div>
                        <div class="text-xs text-gray-500">{{ $feature->description }}</div>
                    </div>
                    <label class="inline-flex items-center gap-2">
                        <input type="hidden" name="enabled" value="0">
                        <input type="checkbox" name="enabled" value="1" @checked($enabled) onchange="this.form.submit()" class="h-4 w-4 rounded border-gray-300 text-indigo-600">
                        <span>{{ $enabled ? 'Enabled' : 'Disabled' }}</span>
                    </label>
                </form>
            @endforeach
        </div>
    </div>
@endsection

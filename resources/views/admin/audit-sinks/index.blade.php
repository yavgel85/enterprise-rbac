@extends('layouts.app')
@section('title', 'Audit sinks')
@section('header', 'Audit sinks')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5 text-sm text-gray-600">
                <h3 class="text-base font-semibold text-gray-900 mb-1">Real-time audit forwarding</h3>
                <p>Each audit event is POSTed to the sink endpoint as JSON. When a secret is set, requests carry an
                    <code class="text-xs">X-Audit-Signature: sha256=&lt;hmac&gt;</code> header so your SIEM can verify authenticity.</p>
            </div>

            @forelse ($sinks as $sink)
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold text-gray-900">
                                {{ $sink->name }}
                                @if ($sink->is_active)
                                    <span class="ml-1 inline-flex items-center rounded-md bg-green-50 px-2 py-0.5 text-xs font-medium text-green-700 ring-1 ring-green-200">active</span>
                                @else
                                    <span class="ml-1 inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600 ring-1 ring-gray-200">paused</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500 font-mono break-all">{{ $sink->endpoint }}</div>
                            <div class="mt-1 text-xs text-gray-500">
                                Events: {{ $sink->events ? implode(', ', $sink->events) : 'all' }}
                            </div>
                            @if ($sink->last_delivered_at)
                                <div class="mt-1 text-xs text-green-600">Last delivered {{ $sink->last_delivered_at->diffForHumans() }}</div>
                            @endif
                            @if ($sink->last_error)
                                <div class="mt-1 text-xs text-red-600">Last error: {{ $sink->last_error }} ({{ $sink->last_failed_at?->diffForHumans() }})</div>
                            @endif
                        </div>
                        <form method="POST" action="{{ route('admin.audit-sinks.destroy', [$tenant, $sink]) }}" onsubmit="return confirm('Delete this sink?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-xs text-red-600">Delete</button>
                        </form>
                    </div>

                    <form method="POST" action="{{ route('admin.audit-sinks.update', [$tenant, $sink]) }}" class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm border-t border-gray-100 pt-4">
                        @csrf
                        @method('PUT')
                        <input type="text" name="name" value="{{ $sink->name }}" required class="rounded-md border-gray-300 shadow-sm px-3 py-2 border" placeholder="Name">
                        <input type="url" name="endpoint" value="{{ $sink->endpoint }}" required class="rounded-md border-gray-300 shadow-sm px-3 py-2 border" placeholder="https://...">
                        <input type="text" name="secret" value="" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border" placeholder="Secret (leave blank to keep)">
                        <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" @checked($sink->is_active)> Active</label>
                        <button type="submit" class="sm:col-span-2 rounded-md bg-gray-800 px-3 py-2 font-semibold text-white hover:bg-gray-700">Save changes</button>
                    </form>
                </div>
            @empty
                <div class="bg-white rounded-lg border border-gray-200 shadow-sm px-5 py-10 text-center text-sm text-gray-500">No sinks configured.</div>
            @endforelse
        </div>

        <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900 mb-3">New sink</h3>
            @if ($errors->any())
                <div class="mb-3 rounded-md bg-red-50 p-3 text-xs text-red-700">{{ $errors->first() }}</div>
            @endif
            <form method="POST" action="{{ route('admin.audit-sinks.store', $tenant) }}" class="space-y-3 text-sm">
                @csrf
                <div>
                    <label class="block font-medium text-gray-700">Name</label>
                    <input type="text" name="name" value="{{ old('name') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Endpoint URL</label>
                    <input type="url" name="endpoint" value="{{ old('endpoint') }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border" placeholder="https://siem.example.com/audit">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Signing secret (optional)</label>
                    <input type="text" name="secret" value="{{ old('secret') }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                </div>
                <div>
                    <label class="block font-medium text-gray-700">Events (optional)</label>
                    <select name="events[]" multiple size="6" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border text-xs">
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ $action }}</option>
                        @endforeach
                    </select>
                    <p class="mt-1 text-xs text-gray-400">Leave empty to forward every action.</p>
                </div>
                <label class="inline-flex items-center gap-2"><input type="checkbox" name="is_active" value="1" checked> Active</label>
                <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Create sink</button>
            </form>
        </div>
    </div>
@endsection

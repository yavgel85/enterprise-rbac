@extends('layouts.app')
@section('title', $deal->title)
@section('header', $deal->title)

@section('content')
    <div class="flex items-center justify-between mb-6">
        <p class="text-sm text-gray-500">
            Owner: <strong>{{ $deal->owner?->name ?? 'unassigned' }}</strong> ·
            Stage: <strong>{{ $deal->stage->label() }}</strong> ·
            Status: <strong>{{ $deal->status->label() }}</strong>
        </p>
        <div class="flex gap-2">
            @can('approve', $deal)
                @if (! $pendingApproval)
                    <form method="POST" action="{{ route('crm.deals.approve', [$tenant, $deal]) }}">
                        @csrf
                        <button type="submit" class="rounded-md bg-green-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-green-500">Approve & close</button>
                    </form>
                @endif
            @endcan
            @can('update', $deal)
                <a href="{{ route('crm.deals.edit', [$tenant, $deal]) }}" class="rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-900 ring-1 ring-gray-300 hover:bg-gray-50">Edit</a>
            @endcan
        </div>
    </div>

    @if ($pendingApproval)
        <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
            <strong>Pending approval</strong> — step {{ $pendingApproval->current_step }} of {{ $pendingApproval->steps->count() }}.
            Requested by {{ $pendingApproval->requester?->name }}.
            <a href="{{ route('crm.approvals.index', $tenant) }}" class="underline">View approval queue</a>
        </div>
    @endif

    <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-3 text-sm">
        <div class="flex"><span class="w-40 text-gray-500">Amount</span><span>{{ number_format((float) $deal->amount, 2) }} {{ $deal->currency }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Probability</span><span>{{ $deal->probability }}%</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Company</span><span>{{ $deal->company?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Contact</span><span>{{ $deal->contact?->fullName() ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Department</span><span>{{ $deal->department?->name ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Expected close</span><span>{{ $deal->expected_close_date?->format('Y-m-d') ?? '—' }}</span></div>
        <div class="flex"><span class="w-40 text-gray-500">Closed at</span><span>{{ $deal->closed_at?->format('Y-m-d H:i') ?? '—' }}</span></div>
    </div>

    @if (auth()->user()->hasPermission(\App\Enums\Permission::PermissionsAssign))
        <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
            <h3 class="text-base font-semibold text-gray-900">Instance permissions (ReBAC)</h3>
            <p class="text-xs text-gray-500 mb-4">Grant a single user access to <em>this deal only</em>, regardless of their role.</p>

            @if ($instanceGrants->isNotEmpty())
                <ul class="mb-4 divide-y divide-gray-100 text-sm">
                    @foreach ($instanceGrants as $grant)
                        <li class="flex items-center justify-between py-2">
                            <span>
                                <strong>{{ $grant->user?->name }}</strong>
                                <span class="font-mono text-xs text-gray-600">{{ $grant->permission?->slug }}</span>
                                @if ($grant->expires_at)
                                    <span class="text-xs text-gray-400">· until {{ $grant->expires_at->format('Y-m-d H:i') }}</span>
                                @endif
                            </span>
                            <form method="POST" action="{{ route('crm.deals.instance-permissions.revoke', [$tenant, $deal, $grant]) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600">Revoke</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif

            <form method="POST" action="{{ route('crm.deals.instance-permissions.grant', [$tenant, $deal]) }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3 text-sm">
                @csrf
                <select name="user_id" required class="rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    @foreach ($assignableUsers as $assignable)
                        <option value="{{ $assignable->id }}">{{ $assignable->name }}</option>
                    @endforeach
                </select>
                <select name="permission_id" required class="rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                    @foreach ($instancePermissions as $permission)
                        <option value="{{ $permission->id }}">{{ $permission->slug }}</option>
                    @endforeach
                </select>
                <input type="datetime-local" name="expires_at" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border" title="Optional expiry">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 font-semibold text-white hover:bg-indigo-500">Grant</button>
            </form>
        </div>
    @endif

    @include('crm._custom-fields-show', ['model' => $deal, 'modelType' => \App\Models\Deal::class])

    @include('crm._attachments', ['attachable' => $deal, 'attachableType' => 'deal', 'tenant' => $tenant])
@endsection

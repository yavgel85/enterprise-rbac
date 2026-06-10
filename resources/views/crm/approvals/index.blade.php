@extends('layouts.app')
@section('title', 'Approvals')
@section('header', 'Approval queue')

@section('content')
    <div class="space-y-8">
        <section>
            <h3 class="text-base font-semibold text-gray-900 mb-3">Pending</h3>
            @forelse ($pending as $req)
                @php($current = $req->currentStep())
                <div class="mb-4 bg-white rounded-lg border border-gray-200 shadow-sm p-5">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="font-semibold text-gray-900">
                                @if ($req->approvable)
                                    <a href="{{ route('crm.deals.show', [$tenant, $req->approvable]) }}" class="text-indigo-600 hover:underline">{{ $req->approvable->title }}</a>
                                    <span class="text-sm text-gray-500">· {{ number_format((float) $req->approvable->amount, 0) }} {{ $req->approvable->currency }}</span>
                                @else
                                    <span class="text-gray-500">Removed resource</span>
                                @endif
                            </div>
                            <div class="text-xs text-gray-500">Requested by {{ $req->requester?->name ?? '—' }} · {{ $req->created_at->diffForHumans() }}</div>
                        </div>
                        <span class="inline-flex items-center rounded-md bg-amber-100 px-2 py-1 text-xs font-medium text-amber-800">Step {{ $req->current_step }} / {{ $req->steps->count() }}</span>
                    </div>

                    <ol class="mt-4 flex flex-wrap gap-2 text-xs">
                        @foreach ($req->steps as $step)
                            <li class="rounded-md px-2 py-1 ring-1 ring-inset
                                {{ $step->decision === 'approved' ? 'bg-green-50 text-green-700 ring-green-200' : '' }}
                                {{ $step->decision === 'rejected' ? 'bg-red-50 text-red-700 ring-red-200' : '' }}
                                {{ $step->decision === null && $step->step === $req->current_step ? 'bg-amber-50 text-amber-800 ring-amber-200' : '' }}
                                {{ $step->decision === null && $step->step !== $req->current_step ? 'bg-gray-50 text-gray-500 ring-gray-200' : '' }}">
                                {{ $step->step }}. {{ $step->role?->name ?? 'Any' }}
                                @if ($step->decision)
                                    — {{ $step->decision }} by {{ $step->decider?->name }}
                                @endif
                            </li>
                        @endforeach
                    </ol>

                    @if ($req->canBeDecidedBy(auth()->user()))
                        <form method="POST" action="{{ route('crm.approvals.decide', [$tenant, $req]) }}" class="mt-4 flex flex-wrap items-center gap-2">
                            @csrf
                            <input type="text" name="note" placeholder="Note (optional)" class="rounded-md border-gray-300 shadow-sm px-3 py-2 border text-sm flex-1 min-w-48">
                            <button type="submit" name="decision" value="approve" class="rounded-md bg-green-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-green-500">Approve step</button>
                            <button type="submit" name="decision" value="reject" class="rounded-md bg-red-600 px-3.5 py-2 text-sm font-semibold text-white hover:bg-red-500">Reject</button>
                        </form>
                    @else
                        <p class="mt-3 text-xs text-gray-400">Awaiting the {{ $current?->role?->name ?? 'assigned' }} approver (you cannot decide this step).</p>
                    @endif
                </div>
            @empty
                <p class="text-sm text-gray-500">No pending approvals.</p>
            @endforelse
        </section>

        <section>
            <h3 class="text-base font-semibold text-gray-900 mb-3">Recently decided</h3>
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Resource</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Status</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Requested by</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-700">Updated</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($decided as $req)
                            <tr>
                                <td class="px-4 py-3 text-gray-900">{{ $req->approvable?->title ?? '—' }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-md px-2 py-1 text-xs font-medium {{ $req->status === \App\Enums\ApprovalStatus::Approved ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $req->status->label() }}</span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ $req->requester?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $req->updated_at->diffForHumans() }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Nothing decided yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

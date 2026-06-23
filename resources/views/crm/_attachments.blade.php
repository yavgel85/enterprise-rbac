{{-- Expects: $attachable (model), $attachableType (short key), $tenant --}}
@can('view', $attachable)
    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Attachments</h3>

        @php($attachments = $attachable->attachments)
        @if ($attachments->isNotEmpty())
            <ul class="mb-4 divide-y divide-gray-100 text-sm">
                @foreach ($attachments as $attachment)
                    <li class="flex items-center justify-between py-2">
                        <span class="min-w-0">
                            <a class="text-indigo-600 hover:underline truncate"
                                href="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('crm.attachments.download', now()->addMinutes(config('attachments.download_ttl_minutes')), ['tenant' => $tenant, 'attachment' => $attachment]) }}">
                                {{ $attachment->name }}
                            </a>
                            <span class="text-xs text-gray-400">· {{ $attachment->humanSize() }} · {{ $attachment->uploader?->name ?? 'system' }}</span>
                        </span>
                        @can('update', $attachable)
                            <form method="POST" action="{{ route('crm.attachments.destroy', [$tenant, $attachment]) }}"
                                onsubmit="return confirm('Delete this file?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-xs text-red-600">Delete</button>
                            </form>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @else
            <p class="text-sm text-gray-500 mb-4">No files attached yet.</p>
        @endif

        @can('update', $attachable)
            <form method="POST" action="{{ route('crm.attachments.store', $tenant) }}" enctype="multipart/form-data"
                class="flex items-center gap-3 text-sm">
                @csrf
                <input type="hidden" name="attachable_type" value="{{ $attachableType }}">
                <input type="hidden" name="attachable_id" value="{{ $attachable->id }}">
                <input type="file" name="file" required class="block text-sm text-gray-700">
                <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 font-semibold text-white hover:bg-indigo-500">Upload</button>
            </form>
            @error('file') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        @endcan
    </div>
@endcan

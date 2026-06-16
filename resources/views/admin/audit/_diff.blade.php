@php
    $old = $log->old_values ?? [];
    $new = $log->new_values ?? [];
    $keys = collect(array_keys($old))->merge(array_keys($new))->unique()->values();
    $format = function ($value) {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        return $value === null ? '∅' : (string) $value;
    };
@endphp

<div class="rounded-md border border-gray-200 bg-gray-50 p-3 text-xs">
    @if ($keys->isNotEmpty())
        <table class="min-w-full">
            <thead>
                <tr class="text-gray-500">
                    <th class="text-left font-medium pr-4 pb-1">Field</th>
                    <th class="text-left font-medium pr-4 pb-1">Before</th>
                    <th class="text-left font-medium pb-1">After</th>
                </tr>
            </thead>
            <tbody class="align-top font-mono">
                @foreach ($keys as $key)
                    @php
                        $before = $old[$key] ?? null;
                        $after = array_key_exists($key, $new) ? $new[$key] : null;
                        $changed = ($old[$key] ?? null) != ($new[$key] ?? null);
                    @endphp
                    <tr @class(['border-t border-gray-200', 'bg-amber-50' => $changed])>
                        <td class="pr-4 py-1 text-gray-700">{{ $key }}</td>
                        <td class="pr-4 py-1 text-red-600 break-all">{{ $format($before) }}</td>
                        <td class="py-1 text-green-700 break-all">{{ $format($after) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if (! empty($log->metadata))
        <div class="@if ($keys->isNotEmpty()) mt-3 border-t border-gray-200 pt-2 @endif">
            <div class="text-gray-500 mb-1 font-medium">Metadata</div>
            <pre class="whitespace-pre-wrap break-all text-gray-700">{{ json_encode($log->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) }}</pre>
        </div>
    @endif

    @if ($log->url)
        <div class="mt-2 text-gray-400 break-all">{{ $log->url }}</div>
    @endif
</div>

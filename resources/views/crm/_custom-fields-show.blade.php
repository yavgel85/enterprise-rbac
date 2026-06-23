{{-- Expects: $model (entity), $modelType (FQCN) --}}
@php
    use App\Enums\CustomFieldType;
    $defs = \App\Models\CustomFieldDefinition::forModel($modelType)->get();
    $vals = $model->customFieldValues()->with('definition')->get()->keyBy(fn ($v) => $v->definition->key);
    $cfUserNames = $defs->contains(fn ($d) => $d->type === CustomFieldType::User)
        ? \App\Models\User::query()->pluck('name', 'id')
        : collect();
@endphp

@if ($defs->isNotEmpty())
    <div class="mt-6 bg-white rounded-lg border border-gray-200 shadow-sm p-6">
        <h3 class="text-base font-semibold text-gray-900 mb-4">Custom fields</h3>
        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
            @foreach ($defs as $definition)
                @php($value = $vals->get($definition->key)?->typedValue())
                <div class="flex">
                    <dt class="w-40 text-gray-500">{{ $definition->label }}</dt>
                    <dd>
                        @switch($definition->type)
                            @case(CustomFieldType::Boolean)
                                {{ $value ? 'Yes' : 'No' }}
                                @break
                            @case(CustomFieldType::User)
                                {{ $value ? ($cfUserNames[$value] ?? '—') : '—' }}
                                @break
                            @case(CustomFieldType::Date)
                                {{ $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : ($value ?? '—') }}
                                @break
                            @default
                                {{ ($value === null || $value === '') ? '—' : $value }}
                        @endswitch
                    </dd>
                </div>
            @endforeach
        </dl>
    </div>
@endif

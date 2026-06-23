{{-- Expects: $model (entity or null), $modelType (FQCN) --}}
@php
    use App\Enums\CustomFieldType;
    $definitions = \App\Models\CustomFieldDefinition::forModel($modelType)->get();
    $existing = $model
        ? $model->customFieldValues()->with('definition')->get()->keyBy(fn ($v) => $v->definition->key)
        : collect();
    $cfUsers = $definitions->contains(fn ($d) => $d->type === CustomFieldType::User)
        ? \App\Models\User::query()->orderBy('name')->get(['id', 'name'])
        : collect();
@endphp

@if ($definitions->isNotEmpty())
    <div class="mt-6 border-t border-gray-200 pt-6">
        <h3 class="text-sm font-semibold text-gray-700 mb-4">Custom fields</h3>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @foreach ($definitions as $definition)
                @php
                    $name = "custom_fields[{$definition->key}]";
                    $current = old("custom_fields.{$definition->key}", $existing->get($definition->key)?->typedValue());
                @endphp
                <div class="{{ $definition->type === CustomFieldType::Boolean ? 'sm:col-span-2 flex items-center gap-2' : '' }}">
                    @if ($definition->type === CustomFieldType::Boolean)
                        <input type="hidden" name="custom_fields[{{ $definition->key }}]" value="0">
                        <input type="checkbox" name="{{ $name }}" value="1" @checked($current)
                            class="rounded border-gray-300">
                        <label class="text-sm font-medium text-gray-700">{{ $definition->label }}</label>
                    @else
                        <label class="block text-sm font-medium text-gray-700">
                            {{ $definition->label }}@if ($definition->required) <span class="text-red-500">*</span>@endif
                        </label>
                        @switch($definition->type)
                            @case(CustomFieldType::Select)
                                <select name="{{ $name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                    <option value="">— none —</option>
                                    @foreach ($definition->options ?? [] as $option)
                                        <option value="{{ $option }}" @selected((string) $current === (string) $option)>{{ $option }}</option>
                                    @endforeach
                                </select>
                                @break
                            @case(CustomFieldType::User)
                                <select name="{{ $name }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                    <option value="">— none —</option>
                                    @foreach ($cfUsers as $u)
                                        <option value="{{ $u->id }}" @selected((int) $current === $u->id)>{{ $u->name }}</option>
                                    @endforeach
                                </select>
                                @break
                            @case(CustomFieldType::Number)
                                <input type="number" step="any" name="{{ $name }}" value="{{ $current }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                @break
                            @case(CustomFieldType::Date)
                                <input type="date" name="{{ $name }}" value="{{ $current instanceof \DateTimeInterface ? $current->format('Y-m-d') : $current }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                                @break
                            @default
                                <input type="text" name="{{ $name }}" value="{{ $current }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
                        @endswitch
                        @error("custom_fields.{$definition->key}") <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif

@php($values = $company ?? null)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">Name</label>
        <input type="text" name="name" value="{{ old('name', $values?->name) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
        @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Industry</label>
        <input type="text" name="industry" value="{{ old('industry', $values?->industry) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Email</label>
        <input type="email" name="email" value="{{ old('email', $values?->email) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Phone</label>
        <input type="text" name="phone" value="{{ old('phone', $values?->phone) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Website</label>
        <input type="url" name="website" value="{{ old('website', $values?->website) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Address</label>
        <textarea name="address" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('address', $values?->address) }}</textarea>
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Notes</label>
        <textarea name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('notes', $values?->notes) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @foreach (App\Enums\CompanyStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('status', $values?->status?->value) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Owner</label>
        <select name="owner_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— unassigned —</option>
            @foreach ($users as $user)
                <option value="{{ $user->id }}" @selected(old('owner_id', $values?->owner_id) == $user->id)>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>
</div>

@include('crm._custom-fields', ['model' => $values, 'modelType' => \App\Models\Company::class])

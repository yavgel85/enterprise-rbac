@php($values = $contact ?? null)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div>
        <label class="block text-sm font-medium text-gray-700">First name</label>
        <input type="text" name="first_name" value="{{ old('first_name', $values?->first_name) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
        @error('first_name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Last name</label>
        <input type="text" name="last_name" value="{{ old('last_name', $values?->last_name) }}"
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
    <div>
        <label class="block text-sm font-medium text-gray-700">Position</label>
        <input type="text" name="position" value="{{ old('position', $values?->position) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Company</label>
        <select name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— none —</option>
            @foreach ($companies as $company)
                <option value="{{ $company->id }}" @selected(old('company_id', $values?->company_id) == $company->id)>{{ $company->name }}</option>
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

@include('crm._custom-fields', ['model' => $values, 'modelType' => \App\Models\Contact::class])

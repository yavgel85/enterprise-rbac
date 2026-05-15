@php($values = $deal ?? null)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input type="text" name="title" value="{{ old('title', $values?->title) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
        @error('title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Amount</label>
        <input type="number" step="0.01" name="amount" value="{{ old('amount', $values?->amount) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Currency</label>
        <input type="text" name="currency" maxlength="3" value="{{ old('currency', $values?->currency ?? 'USD') }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border uppercase">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Stage</label>
        <select name="stage" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @foreach (App\Enums\DealStage::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('stage', $values?->stage?->value) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @foreach (App\Enums\DealStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('status', $values?->status?->value ?? 'draft') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Probability (%)</label>
        <input type="number" min="0" max="100" name="probability" value="{{ old('probability', $values?->probability ?? 0) }}" required
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Expected close date</label>
        <input type="date" name="expected_close_date" value="{{ old('expected_close_date', $values?->expected_close_date?->format('Y-m-d')) }}"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Company</label>
        <select name="company_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— none —</option>
            @foreach ($companies as $c)
                <option value="{{ $c->id }}" @selected(old('company_id', $values?->company_id) == $c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Contact</label>
        <select name="contact_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— none —</option>
            @foreach ($contacts as $c)
                <option value="{{ $c->id }}" @selected(old('contact_id', $values?->contact_id) == $c->id)>{{ $c->first_name }} {{ $c->last_name }}</option>
            @endforeach
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-gray-700">Department</label>
        <select name="department_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— none —</option>
            @foreach ($departments as $d)
                <option value="{{ $d->id }}" @selected(old('department_id', $values?->department_id) == $d->id)>{{ $d->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Owner</label>
        <select name="owner_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— unassigned —</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected(old('owner_id', $values?->owner_id) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
</div>

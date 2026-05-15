@php($values = $task ?? null)
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Title</label>
        <input type="text" name="title" value="{{ old('title', $values?->title) }}" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div class="sm:col-span-2">
        <label class="block text-sm font-medium text-gray-700">Description</label>
        <textarea name="description" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">{{ old('description', $values?->description) }}</textarea>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Due date</label>
        <input type="datetime-local" name="due_date" value="{{ old('due_date', $values?->due_date?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Assignee</label>
        <select name="assignee_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            <option value="">— unassigned —</option>
            @foreach ($users as $u)
                <option value="{{ $u->id }}" @selected(old('assignee_id', $values?->assignee_id) == $u->id)>{{ $u->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Priority</label>
        <select name="priority" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @foreach (App\Enums\TaskPriority::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('priority', $values?->priority?->value ?? 'normal') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-sm font-medium text-gray-700">Status</label>
        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm px-3 py-2 border">
            @foreach (App\Enums\TaskStatus::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('status', $values?->status?->value ?? 'open') === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
    </div>
</div>

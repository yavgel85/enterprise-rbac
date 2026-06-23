<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Enums\CustomFieldType;
use App\Http\Controllers\Controller;
use App\Models\CustomFieldDefinition;
use App\Models\Tenant;
use App\Support\CustomFieldModels;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomFieldController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $definitions = CustomFieldDefinition::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('model_type')
            ->orderBy('position')
            ->get()
            ->groupBy('model_type');

        return view('admin.custom-fields.index', [
            'tenant' => $tenant,
            'definitions' => $definitions,
            'models' => CustomFieldModels::options(),
            'types' => CustomFieldType::cases(),
        ]);
    }

    public function store(Request $request, LogAuditEvent $audit, Tenant $tenant): RedirectResponse
    {
        $data = $request->validate([
            'model' => ['required', Rule::in(array_keys(CustomFieldModels::MAP))],
            'label' => ['required', 'string', 'max:255'],
            'key' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
            'type' => ['required', Rule::enum(CustomFieldType::class)],
            'required' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $modelType = CustomFieldModels::classFor($data['model']);
        $type = CustomFieldType::from($data['type']);

        $exists = CustomFieldDefinition::query()
            ->where('tenant_id', $tenant->id)
            ->where('model_type', $modelType)
            ->where('key', $data['key'])
            ->exists();

        if ($exists) {
            return back()->withInput()->with('error', 'A field with this key already exists for that model.');
        }

        $definition = CustomFieldDefinition::create([
            'tenant_id' => $tenant->id,
            'model_type' => $modelType,
            'key' => $data['key'],
            'label' => $data['label'],
            'type' => $type,
            'options' => $this->parseOptions($type, $data['options'] ?? null),
            'required' => $request->boolean('required'),
            'position' => (int) ($data['position'] ?? 0),
        ]);

        $audit->handle(AuditAction::CustomFieldCreated, $definition, [
            'model' => $data['model'],
            'key' => $definition->key,
        ]);

        return back()->with('status', 'Custom field created.');
    }

    public function update(Request $request, LogAuditEvent $audit, Tenant $tenant, CustomFieldDefinition $customField): RedirectResponse
    {
        $data = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'required' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $customField->update([
            'label' => $data['label'],
            'required' => $request->boolean('required'),
            'options' => $this->parseOptions($customField->type, $data['options'] ?? null),
            'position' => (int) ($data['position'] ?? $customField->position),
        ]);

        $audit->handle(AuditAction::CustomFieldUpdated, $customField, ['key' => $customField->key]);

        return back()->with('status', 'Custom field updated.');
    }

    public function destroy(LogAuditEvent $audit, Tenant $tenant, CustomFieldDefinition $customField): RedirectResponse
    {
        $audit->handle(AuditAction::CustomFieldDeleted, $customField, ['key' => $customField->key]);

        $customField->delete();

        return back()->with('status', 'Custom field deleted.');
    }

    /**
     * @return list<string>|null
     */
    private function parseOptions(CustomFieldType $type, ?string $options): ?array
    {
        if (! $type->usesOptions() || $options === null) {
            return null;
        }

        return collect(preg_split('/\r\n|\r|\n/', $options))
            ->map(fn (string $line) => trim($line))
            ->filter()
            ->values()
            ->all();
    }
}

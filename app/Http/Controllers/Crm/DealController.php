<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DealController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->latest()
            ->paginate(20);

        return view('crm.deals.index', compact('deals', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Deal::class);

        return view('crm.deals.create', $this->formData($tenant));
    }

    public function store(DealRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Deal::class);

        $deal = Deal::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal created.');
    }

    public function show(Tenant $tenant, Deal $deal): View
    {
        $this->authorize('view', $deal);

        $deal->load(['company:id,name', 'contact:id,first_name,last_name', 'owner:id,name', 'department:id,name']);

        return view('crm.deals.show', compact('deal', 'tenant'));
    }

    public function edit(Tenant $tenant, Deal $deal): View
    {
        $this->authorize('update', $deal);

        return view('crm.deals.edit', $this->formData($tenant, $deal));
    }

    public function update(DealRequest $request, Tenant $tenant, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $deal->update($request->validated());

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal updated.');
    }

    public function destroy(Tenant $tenant, Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $deal->delete();

        return redirect()->route('crm.deals.index', $tenant)
            ->with('status', 'Deal deleted.');
    }

    public function approve(Tenant $tenant, Deal $deal, LogAuditEvent $audit): RedirectResponse
    {
        $this->authorize('approve', $deal);

        $deal->update([
            'stage' => DealStage::Won->value,
            'status' => DealStatus::Closed->value,
            'closed_at' => now(),
        ]);

        $audit->handle(AuditAction::DealApproved, $deal);

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal approved and closed.');
    }

    private function formData(Tenant $tenant, ?Deal $deal = null): array
    {
        return [
            'tenant' => $tenant,
            'deal' => $deal,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
        ];
    }
}

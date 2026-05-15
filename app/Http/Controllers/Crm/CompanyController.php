<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanyRequest;
use App\Models\Company;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CompanyController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Company::class);

        $companies = Company::query()
            ->with('owner:id,name')
            ->latest()
            ->paginate(20);

        return view('crm.companies.index', compact('companies', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Company::class);

        $users = User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

        return view('crm.companies.create', compact('tenant', 'users'));
    }

    public function store(CompanyRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Company::class);

        $company = Company::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('crm.companies.show', [$tenant, $company])
            ->with('status', 'Company created.');
    }

    public function show(Tenant $tenant, Company $company): View
    {
        $this->authorize('view', $company);

        $company->load(['owner:id,name', 'creator:id,name', 'contacts', 'deals']);

        return view('crm.companies.show', compact('company', 'tenant'));
    }

    public function edit(Tenant $tenant, Company $company): View
    {
        $this->authorize('update', $company);

        $users = User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);

        return view('crm.companies.edit', compact('company', 'tenant', 'users'));
    }

    public function update(CompanyRequest $request, Tenant $tenant, Company $company): RedirectResponse
    {
        $this->authorize('update', $company);

        $company->update($request->validated());

        return redirect()->route('crm.companies.show', [$tenant, $company])
            ->with('status', 'Company updated.');
    }

    public function destroy(Tenant $tenant, Company $company): RedirectResponse
    {
        $this->authorize('delete', $company);

        $company->delete();

        return redirect()->route('crm.companies.index', $tenant)
            ->with('status', 'Company deleted.');
    }
}

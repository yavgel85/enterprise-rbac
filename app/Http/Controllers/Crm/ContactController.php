<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\ContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Contact::class);

        $contacts = Contact::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->latest()
            ->paginate(20);

        return view('crm.contacts.index', compact('contacts', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Contact::class);

        return view('crm.contacts.create', [
            'tenant' => $tenant,
            'users' => $this->users($tenant),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(ContactRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Contact::class);

        $contact = Contact::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('crm.contacts.show', [$tenant, $contact])
            ->with('status', 'Contact created.');
    }

    public function show(Tenant $tenant, Contact $contact): View
    {
        $this->authorize('view', $contact);

        $contact->load(['company:id,name', 'owner:id,name']);

        return view('crm.contacts.show', compact('contact', 'tenant'));
    }

    public function edit(Tenant $tenant, Contact $contact): View
    {
        $this->authorize('update', $contact);

        return view('crm.contacts.edit', [
            'tenant' => $tenant,
            'contact' => $contact,
            'users' => $this->users($tenant),
            'companies' => Company::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(ContactRequest $request, Tenant $tenant, Contact $contact): RedirectResponse
    {
        $this->authorize('update', $contact);

        $contact->update($request->validated());

        return redirect()->route('crm.contacts.show', [$tenant, $contact])
            ->with('status', 'Contact updated.');
    }

    public function destroy(Tenant $tenant, Contact $contact): RedirectResponse
    {
        $this->authorize('delete', $contact);

        $contact->delete();

        return redirect()->route('crm.contacts.index', $tenant)
            ->with('status', 'Contact deleted.');
    }

    private function users(Tenant $tenant)
    {
        return User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);
    }
}

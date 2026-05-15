<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Tenant\BootstrapTenant;
use App\Http\Controllers\Controller;
use App\Models\Feature;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TenantController extends Controller
{
    public function index(): View
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'departments', 'roles'])
            ->withTrashed()
            ->latest()
            ->paginate(30);

        return view('super-admin.tenants.index', compact('tenants'));
    }

    public function create(): View
    {
        return view('super-admin.tenants.create');
    }

    public function store(Request $request, BootstrapTenant $bootstrap): RedirectResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:tenants,slug'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $tenant = Tenant::create([
            'name' => $payload['name'],
            'slug' => $payload['slug'],
            'is_active' => (bool) ($payload['is_active'] ?? true),
        ]);

        $bootstrap->handle($tenant);

        return redirect()->route('super-admin.tenants.show', $tenant)
            ->with('status', 'Tenant created and bootstrapped.');
    }

    public function show(Tenant $tenant): View
    {
        $tenant->load(['features']);
        $allFeatures = Feature::query()->orderBy('name')->get();

        return view('super-admin.tenants.show', compact('tenant', 'allFeatures'));
    }

    public function toggle(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        return back()->with('status', $tenant->is_active ? 'Tenant activated.' : 'Tenant suspended.');
    }

    public function toggleFeature(Request $request, Tenant $tenant, Feature $feature): RedirectResponse
    {
        $enabled = $request->boolean('enabled');

        $tenant->features()->syncWithoutDetaching([
            $feature->id => ['is_enabled' => $enabled],
        ]);

        return back()->with('status', "Feature {$feature->slug} ".($enabled ? 'enabled' : 'disabled').'.');
    }
}

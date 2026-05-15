<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Department::class);

        $departments = Department::query()
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.departments.index', compact('departments', 'tenant'));
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Department::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        Department::create([
            'tenant_id' => $tenant->id,
            'name' => $payload['name'],
            'slug' => Str::slug($payload['name']).'-'.Str::lower(Str::random(4)),
        ]);

        return back()->with('status', 'Department created.');
    }

    public function update(Request $request, Tenant $tenant, Department $department): RedirectResponse
    {
        $this->authorize('update', $department);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $department->update($payload);

        return back()->with('status', 'Department updated.');
    }

    public function destroy(Tenant $tenant, Department $department): RedirectResponse
    {
        $this->authorize('delete', $department);

        $department->delete();

        return back()->with('status', 'Department deleted.');
    }
}

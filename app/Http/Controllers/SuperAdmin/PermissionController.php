<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(): View
    {
        $modules = Module::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('slug')])
            ->orderBy('sort_order')
            ->get();

        return view('super-admin.permissions.index', compact('modules'));
    }

    public function show(Permission $permission): View
    {
        $permission->load('module');

        $usage = [
            'roles' => $permission->roles()->count(),
            'direct_users' => $permission->users()->count(),
        ];

        return view('super-admin.permissions.show', compact('permission', 'usage'));
    }
}

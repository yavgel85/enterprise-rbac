<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Actions\Authorization\PermissionUsageReport;
use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(PermissionUsageReport $report): View
    {
        $modules = Module::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('slug')])
            ->orderBy('sort_order')
            ->get();

        $usage = $report->handle();
        $usageWindow = (int) config('rbac.usage.window_days', 30);

        return view('super-admin.permissions.index', compact('modules', 'usage', 'usageWindow'));
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

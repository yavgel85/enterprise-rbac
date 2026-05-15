<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Tenant $tenant): View
    {
        $stats = [
            'companies' => Company::count(),
            'contacts' => Contact::count(),
            'deals' => Deal::count(),
            'activities' => Activity::count(),
            'users' => User::query()->where('tenant_id', $tenant->id)->count(),
        ];

        $recentDeals = Deal::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentDeals' => $recentDeals,
        ]);
    }
}

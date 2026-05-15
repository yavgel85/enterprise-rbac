<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Http\Controllers\Controller;
use App\Http\Requests\ActivityRequest;
use App\Models\Activity;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Activity::class);

        $activities = Activity::query()
            ->with('user:id,name')
            ->latest('happened_at')
            ->paginate(20);

        return view('crm.activities.index', compact('activities', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Activity::class);

        return view('crm.activities.create', compact('tenant'));
    }

    public function store(ActivityRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Activity::class);

        Activity::create($request->validated() + [
            'user_id' => $request->user()->id,
        ]);

        return redirect()->route('crm.activities.index', $tenant)
            ->with('status', 'Activity logged.');
    }

    public function destroy(Tenant $tenant, Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $activity->delete();

        return redirect()->route('crm.activities.index', $tenant)
            ->with('status', 'Activity deleted.');
    }
}

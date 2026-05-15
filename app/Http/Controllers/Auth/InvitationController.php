<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Invitation\AcceptInvitation;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->firstOrFail();

        abort_if(! $invitation->isPending(), 410, 'This invitation is no longer valid.');

        return view('auth.invitation', ['invitation' => $invitation]);
    }

    public function accept(Request $request, AcceptInvitation $accept, string $token): RedirectResponse
    {
        $invitation = Invitation::query()
            ->where('token', $token)
            ->firstOrFail();

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $accept->handle($invitation, $payload);

        Auth::login($user);

        return redirect()->route('tenant.dashboard', ['tenant' => $invitation->tenant->slug]);
    }
}

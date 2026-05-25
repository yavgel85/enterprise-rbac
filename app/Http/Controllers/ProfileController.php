<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\Audit\LogAuditEvent;
use App\Actions\Authorization\ChangeOwnPassword;
use App\Enums\AuditAction;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(Request $request): View
    {
        $user = $request->user();

        $sessions = DB::table('sessions')
            ->where('user_id', $user->id)
            ->orderByDesc('last_activity')
            ->get(['id', 'ip_address', 'user_agent', 'last_activity']);

        $currentSessionId = $request->session()->getId();

        return view('profile.show', [
            'user' => $user,
            'sessions' => $sessions,
            'currentSessionId' => $currentSessionId,
        ]);
    }

    public function updatePassword(Request $request, ChangeOwnPassword $action): RedirectResponse
    {
        $payload = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        try {
            $action->handle(
                $request->user(),
                $payload['current_password'],
                $payload['password'],
            );
        } catch (DomainException $e) {
            return back()->withErrors(['current_password' => $e->getMessage()]);
        }

        return back()->with('status', 'Password updated.');
    }

    public function terminateSession(Request $request, string $sessionId, LogAuditEvent $audit): RedirectResponse
    {
        $deleted = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', $sessionId)
            ->delete();

        if ($deleted > 0) {
            $audit->handle(AuditAction::SessionTerminated, $request->user(), [
                'session_id' => $sessionId,
            ]);
        }

        return back()->with('status', 'Session terminated.');
    }

    public function logoutOtherSessions(Request $request, LogAuditEvent $audit): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string'],
        ]);

        $current = $request->session()->getId();

        $count = DB::table('sessions')
            ->where('user_id', $request->user()->id)
            ->where('id', '!=', $current)
            ->delete();

        $audit->handle(AuditAction::SessionTerminated, $request->user(), [
            'count' => $count,
            'reason' => 'logout-other-devices',
        ]);

        return back()->with('status', "Signed out from {$count} other device(s).");
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailVerificationController extends Controller
{
    public function notice(Request $request): RedirectResponse|View
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/');
        }

        return view('auth.verify-email');
    }

    public function verify(EmailVerificationRequest $request, LogAuditEvent $audit): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended('/')->with('status', 'Email already verified.');
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
            $audit->handle(AuditAction::EmailVerified, $request->user());
        }

        return redirect()->intended('/')->with('status', 'Email verified.');
    }

    public function send(Request $request, LogAuditEvent $audit): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return back()->with('status', 'Email already verified.');
        }

        $request->user()->sendEmailVerificationNotification();
        $audit->handle(AuditAction::EmailVerificationSent, $request->user());

        return back()->with('status', 'Verification link sent.');
    }
}

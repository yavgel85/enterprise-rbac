<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.passwords.email');
    }

    public function sendLink(Request $request, LogAuditEvent $audit): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $status = Password::sendResetLink($request->only('email'));

        $user = User::query()->where('email', $request->input('email'))->first();
        if ($user !== null) {
            $audit->handle(AuditAction::PasswordResetRequested, $user, [
                'status' => $status,
            ]);
        }

        return back()->with('status', __(
            'If an account with that email exists, a reset link has been sent.'
        ));
    }

    public function resetForm(string $token, Request $request): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function reset(Request $request, LogAuditEvent $audit): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) use ($audit): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'failed_login_attempts' => 0,
                    'locked_until' => null,
                ])->save();

                event(new PasswordReset($user));

                DB::table('sessions')->where('user_id', $user->id)->delete();

                $audit->handle(AuditAction::PasswordResetCompleted, $user);
            },
        );

        if ($status !== Password::PasswordReset) {
            return back()->withErrors(['email' => __($status)])->withInput($request->only('email'));
        }

        return redirect()->route('login')->with('status', __($status));
    }
}

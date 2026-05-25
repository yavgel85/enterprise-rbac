<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $candidate = User::query()->where('email', $credentials['email'])->first();

        if ($candidate?->isLocked()) {
            throw ValidationException::withMessages([
                'email' => __('Account is temporarily locked. Try again at :time.', [
                    'time' => $candidate->locked_until->format('H:i'),
                ]),
            ]);
        }

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('Those credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('This account is inactive.'),
            ]);
        }

        $user->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        if ($user->is_super_admin) {
            return redirect()->route('super-admin.tenants.index');
        }

        if ($user->tenant) {
            return redirect()->route('tenant.dashboard', ['tenant' => $user->tenant->slug]);
        }

        return redirect('/');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

@extends('layouts.app')

@section('title', 'My profile')
@section('header', 'My profile')

@section('content')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-sm space-y-3">
                <div><span class="text-gray-500">Name</span><div class="font-medium">{{ $user->name }}</div></div>
                <div><span class="text-gray-500">Email</span><div class="font-medium">{{ $user->email }}</div></div>
                <div>
                    <span class="text-gray-500">Email verified</span>
                    <div>{{ $user->email_verified_at?->format('Y-m-d H:i') ?? 'Not verified' }}</div>
                </div>
                <div><span class="text-gray-500">Last login</span><div>{{ $user->last_login_at?->format('Y-m-d H:i') ?? '—' }}</div></div>
            </div>

            @unless ($user->hasVerifiedEmail())
                <form method="POST" action="{{ route('verification.send') }}"
                    class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 text-sm space-y-3">
                    @csrf
                    <h3 class="font-semibold text-gray-900">Email verification</h3>
                    <p class="text-gray-500">Your email is not verified yet.</p>
                    <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-xs font-semibold text-white hover:bg-indigo-500">
                        Resend verification email
                    </button>
                </form>
            @endunless
        </div>

        <div class="lg:col-span-2 space-y-6">
            <form method="POST" action="{{ route('profile.password.update') }}"
                class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
                @csrf
                @method('PUT')
                <h3 class="text-base font-semibold text-gray-900">Change password</h3>

                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">Current password</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                    @error('current_password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                    <input id="password" name="password" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                    @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
                </div>

                <button type="submit"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                    Update password
                </button>
            </form>

            <div class="bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-base font-semibold text-gray-900">Active sessions</h3>
                    <form method="POST" action="{{ route('profile.sessions.logout-others') }}" class="flex items-center gap-2"
                        onsubmit="return confirm('Sign out from all other devices?')">
                        @csrf
                        <input name="password" type="password" placeholder="Your password" required
                            class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-2 py-1 text-xs border">
                        <button type="submit" class="rounded-md bg-amber-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-amber-500">
                            Sign out other devices
                        </button>
                    </form>
                </div>

                @if ($sessions->isEmpty())
                    <p class="text-sm text-gray-500">No persisted sessions (the current session may not yet be in the database).</p>
                @endif

                <ul class="divide-y divide-gray-100 text-sm">
                    @foreach ($sessions as $session)
                        <li class="py-3 flex items-center justify-between gap-4">
                            <div class="space-y-1">
                                <div class="text-gray-900 truncate max-w-xl">{{ $session->user_agent ?? 'Unknown user agent' }}</div>
                                <div class="text-xs text-gray-500">
                                    IP {{ $session->ip_address ?? '—' }} ·
                                    last active {{ \Carbon\Carbon::createFromTimestamp($session->last_activity)->diffForHumans() }}
                                    @if ($session->id === $currentSessionId)
                                        · <span class="text-green-600 font-semibold">this device</span>
                                    @endif
                                </div>
                            </div>
                            @if ($session->id !== $currentSessionId)
                                <form method="POST" action="{{ route('profile.sessions.destroy', $session->id) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-600 hover:text-red-500">Terminate</button>
                                </form>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection

@extends('layouts.app')

@section('title', 'Verify your email')
@section('header', 'Verify your email address')

@section('content')
    <div class="max-w-xl bg-white rounded-lg border border-gray-200 shadow-sm p-6 space-y-4 text-sm">
        <p class="text-gray-700">
            Thanks for signing in. We sent a verification link to
            <strong>{{ auth()->user()->email }}</strong>.
        </p>
        <p class="text-gray-500">
            If you can't find the email, we can send another one.
        </p>

        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit"
                class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                Resend verification email
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-gray-500 hover:text-gray-700">Sign out</button>
        </form>
    </div>
@endsection

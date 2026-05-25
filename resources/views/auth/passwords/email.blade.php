@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-2">Forgot your password?</h2>
    <p class="text-center text-sm text-gray-500 mb-6">Enter your email and we'll send a reset link.</p>

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" required
                value="{{ old('email') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            @error('email') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit"
            class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
            Send password reset link
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-gray-500">
        <a href="{{ route('login') }}" class="text-indigo-600 hover:text-indigo-500">Back to sign in</a>
    </p>
@endsection

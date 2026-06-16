@extends('layouts.guest')

@section('title', 'Confirm password')

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-2">Confirm your password</h2>
    <p class="text-center text-sm text-gray-500 mb-6">This is a sensitive action. Please confirm your password before continuing.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-5">
        @csrf

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required autofocus
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
            Confirm
        </button>
    </form>
@endsection

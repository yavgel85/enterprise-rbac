@extends('layouts.guest')

@section('title', 'Accept invitation')

@section('content')
    <h2 class="text-center text-xl font-semibold text-gray-900 mb-2">Accept invitation</h2>
    <p class="text-center text-sm text-gray-500 mb-6">
        You have been invited to join <strong>{{ $invitation->tenant->name }}</strong>
        as <strong>{{ $invitation->email }}</strong>.
    </p>

    <form method="POST" action="{{ route('invitation.accept', $invitation->token) }}" class="space-y-5">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700">Full name</label>
            <input id="name" name="name" type="text" required value="{{ old('name') }}"
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            @error('name') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
            <input id="password" name="password" type="password" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
            @error('password') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 px-3 py-2 border">
        </div>

        <button type="submit" class="w-full flex justify-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-500">
            Accept and create account
        </button>
    </form>
@endsection

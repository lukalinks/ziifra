@extends('layouts.guest')

@section('title', 'Accept invitation')
@section('meta_description', __('social.invitation_description', ['org' => $invitation->organization->name]))

@section('content')
@if ($invalid)
    <h1 class="text-2xl font-bold text-ziifra-ink">Invitation expired</h1>
    <p class="mt-2 text-sm text-ziifra-muted">This invitation is no longer valid. Ask your administrator to send a new one.</p>
@else
    <h1 class="text-2xl font-bold text-ziifra-ink">Join {{ $invitation->organization->name }}</h1>
    <p class="mt-2 text-sm text-ziifra-muted">
        You have been invited as <strong>{{ $invitation->role->label() }}</strong> on ZIIFRA.
    </p>

    <form method="POST" action="{{ route('invitations.accept.store', $invitation->token) }}" class="mt-8 space-y-5">
        @csrf
        @if ($needsAccount)
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">Your name</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-ziifra-ink">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-ziifra-ink">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
        @elseif (! $existingUser)
            <div>
                <label for="password" class="block text-sm font-medium text-ziifra-ink">Password</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                <p class="mt-1 text-xs text-ziifra-muted">Log in with your existing ZIIFRA account ({{ $invitation->email }}).</p>
            </div>
        @else
            <p class="text-sm text-ziifra-muted">You are logged in as {{ auth()->user()->email }}. Click below to join this organization.</p>
        @endif
        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl">
            Accept invitation
        </button>
    </form>
@endif
@endsection

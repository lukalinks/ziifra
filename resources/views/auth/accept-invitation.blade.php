@extends('layouts.guest')

@section('title', __('auth_pages.accept_invitation.title'))
@section('meta_description', __('social.invitation_description', ['org' => $invitation->organization->name]))

@section('content')
@if ($invalid)
    <h1 class="text-2xl font-bold text-ziifra-ink">{{ __('auth_pages.accept_invitation.expired_heading') }}</h1>
    <p class="mt-2 text-sm text-ziifra-muted">{{ __('auth_pages.accept_invitation.expired_detail') }}</p>
@else
    <h1 class="text-2xl font-bold text-ziifra-ink">{{ __('auth_pages.accept_invitation.join_heading', ['org' => $invitation->organization->name]) }}</h1>
    <p class="mt-2 text-sm text-ziifra-muted">
        {{ __('auth_pages.accept_invitation.invited_as', ['role' => $invitation->role->label()]) }}
    </p>

    <form method="POST" action="{{ route('invitations.accept.store', $invitation->token) }}" class="mt-8 space-y-5">
        @csrf
        @if ($needsAccount)
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">{{ __('auth_pages.accept_invitation.your_name') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-ziifra-ink">{{ __('auth_pages.accept_invitation.password') }}</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-ziifra-ink">{{ __('auth_pages.accept_invitation.confirm_password') }}</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
        @elseif (! $existingUser)
            <div>
                <label for="password" class="block text-sm font-medium text-ziifra-ink">{{ __('auth_pages.accept_invitation.password') }}</label>
                <input id="password" name="password" type="password" required
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                <p class="mt-1 text-xs text-ziifra-muted">{{ __('auth_pages.accept_invitation.existing_account_hint', ['email' => $invitation->email]) }}</p>
            </div>
        @else
            <p class="text-sm text-ziifra-muted">{{ __('auth_pages.accept_invitation.logged_in_hint', ['email' => auth()->user()->email]) }}</p>
        @endif
        <button type="submit" class="ziifra-btn-primary w-full !rounded-xl">
            {{ __('auth_pages.accept_invitation.submit') }}
        </button>
    </form>
@endif
@endsection

@extends('layouts.app')

@section('title', __('team.title'))
@section('header', __('team.title'))

@section('content')
<div class="grid gap-6 lg:grid-cols-2 lg:gap-8">
    <div class="ziifra-team-card order-2 lg:order-1">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('team.invite_heading') }}</h2>
        <form method="POST" action="{{ route('team.invitations.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="email" class="ziifra-label-field">{{ __('team.field_email') }}</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                    class="ziifra-input">
            </div>
            <div>
                <label for="role" class="ziifra-label-field">{{ __('team.field_role') }}</label>
                <select id="role" name="role" required class="ziifra-input">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ziifra-btn-app w-full sm:w-auto">
                {{ __('team.send_invitation') }}
            </button>
        </form>
    </div>

    <div class="order-1 space-y-4 lg:order-2 lg:space-y-6">
        <div class="ziifra-team-card">
            <div class="mb-3 flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('team.members') }}</h2>
                <span class="rounded-full bg-ziifra-accent/10 px-2.5 py-0.5 text-xs font-medium text-ziifra-accent-deep">{{ $members->count() }}</span>
            </div>
            <ul class="divide-y divide-ziifra-line/60">
                @foreach ($members as $member)
                    <li class="flex items-center justify-between gap-3 py-3 text-sm">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="ziifra-list-card-avatar !h-9 !w-9 !text-xs">{{ collect(explode(' ', $member->name))->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->join('') }}</span>
                            <span class="truncate font-medium text-ziifra-ink">{{ $member->name }}</span>
                        </div>
                        <span class="shrink-0 rounded-full bg-ziifra-surface px-2 py-0.5 text-xs text-ziifra-muted">{{ \App\Enums\OrganizationRole::from($member->pivot->role)->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="ziifra-team-card">
            <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('team.pending_invitations') }}</h2>
            @if ($invitations->isEmpty())
                <p class="mt-4 text-sm text-ziifra-muted">{{ __('team.no_pending') }}</p>
            @else
                <ul class="mt-4 divide-y divide-ziifra-line/60">
                    @foreach ($invitations as $invitation)
                        <li class="flex flex-col gap-3 py-3 text-sm sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-ziifra-ink">{{ $invitation->email }}</p>
                                <p class="text-xs text-ziifra-muted">{{ $invitation->role->label() }}</p>
                            </div>
                            <form method="POST" action="{{ route('team.invitations.destroy', $invitation) }}" class="shrink-0">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="w-full rounded-lg border border-red-200 px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 sm:w-auto">{{ __('team.cancel') }}</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection

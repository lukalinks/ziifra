@extends('layouts.app')

@section('title', 'Team')
@section('header', 'Team')

@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Invite team member</h2>
        <form method="POST" action="{{ route('team.invitations.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-ziifra-ink">Email</label>
                <input id="email" name="email" type="email" required value="{{ old('email') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            </div>
            <div>
                <label for="role" class="block text-sm font-medium text-ziifra-ink">Role</label>
                <select id="role" name="role" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                Send invitation
            </button>
        </form>
    </div>

    <div class="space-y-6">
        <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
            <h2 class="text-lg font-semibold text-ziifra-ink">Members</h2>
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($members as $member)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <span class="font-medium text-ziifra-ink">{{ $member->name }}</span>
                        <span class="text-ziifra-muted">{{ \App\Enums\OrganizationRole::from($member->pivot->role)->label() }}</span>
                    </li>
                @endforeach
            </ul>
        </div>

        <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
            <h2 class="text-lg font-semibold text-ziifra-ink">Pending invitations</h2>
            @if ($invitations->isEmpty())
                <p class="mt-4 text-sm text-ziifra-muted">No pending invitations.</p>
            @else
                <ul class="mt-4 divide-y divide-ziifra-line/60">
                    @foreach ($invitations as $invitation)
                        <li class="flex items-center justify-between py-3 text-sm">
                            <span>{{ $invitation->email }} · {{ $invitation->role->label() }}</span>
                            <form method="POST" action="{{ route('team.invitations.destroy', $invitation) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-700">Cancel</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
@endsection

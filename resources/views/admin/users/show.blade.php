@extends('admin.layout')

@section('title', $user->name)

@section('content')
<a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700">← {{ __('admin.nav.users') }}</a>

<h1 class="mt-4 text-2xl font-semibold text-slate-900">{{ $user->name }}</h1>
<p class="text-sm text-slate-600">{{ $user->email }} · Joined {{ $user->created_at->format('d M Y') }}</p>

@if ($user->isSuperAdmin())
    <p class="mt-2 inline-block rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-800">
        {{ __('admin.users.super_admin') }}
    </p>
@endif

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6">
        <h2 class="font-semibold text-slate-900">{{ __('admin.users.organizations') }}</h2>
        @if ($user->organizations->isEmpty())
            <p class="mt-3 text-sm text-slate-500">{{ __('admin.users.no_organizations') }}</p>
        @else
            <ul class="mt-4 space-y-3">
                @foreach ($user->organizations as $organization)
                    <li class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 pb-3 last:border-0 last:pb-0">
                        <div>
                            <a href="{{ route('admin.organizations.show', $organization) }}" class="font-medium text-slate-900 hover:text-indigo-600">
                                {{ $organization->name }}
                            </a>
                            <p class="text-xs text-slate-500 capitalize">{{ $organization->pivot->role }}</p>
                        </div>
                        @if (! $user->isSuperAdmin())
                            <form method="POST" action="{{ route('admin.users.impersonate', $user) }}">
                                @csrf
                                <input type="hidden" name="organization_id" value="{{ $organization->id }}">
                                <button type="submit" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                                    {{ __('admin.impersonate') }}
                                </button>
                            </form>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($user->id !== auth()->id())
        <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6">
            <h2 class="font-semibold text-slate-900">Platform access</h2>
            @if ($user->isSuperAdmin())
                <form method="POST" action="{{ route('admin.users.super-admin', $user) }}" class="mt-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_super_admin" value="0">
                    <button type="submit" class="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-800 hover:bg-red-100">
                        {{ __('admin.users.revoke_super_admin') }}
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('admin.users.super-admin', $user) }}" class="mt-4">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_super_admin" value="1">
                    <button type="submit" class="rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2 text-sm font-medium text-indigo-800 hover:bg-indigo-100">
                        {{ __('admin.users.grant_super_admin') }}
                    </button>
                </form>
            @endif
        </section>
    @endif
</div>
@endsection

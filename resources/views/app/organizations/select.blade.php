@extends('layouts.guest')

@section('title', 'Select organization')

@section('content')
<h1 class="text-2xl font-bold text-ziifra-ink">Select workspace</h1>
<p class="mt-2 text-sm text-ziifra-muted">Choose which company you want to manage.</p>

<form method="POST" action="{{ route('organizations.select.store') }}" class="mt-8 space-y-4">
    @csrf
    @foreach ($organizations as $organization)
        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-ziifra-line/80 p-4 transition hover:border-ziifra-accent/50">
            <input type="radio" name="organization_id" value="{{ $organization->id }}" required
                class="text-ziifra-accent-deep focus:ring-ziifra-accent/25">
            <span class="flex flex-1 items-center justify-between gap-3">
                <span class="font-medium text-ziifra-ink">{{ $organization->name }}</span>
                <a href="{{ route('dashboard', ['organization' => $organization->slug]) }}"
                    class="text-xs font-medium text-ziifra-accent-deep hover:underline"
                    onclick="event.stopPropagation();">Open →</a>
            </span>
        </label>
    @endforeach
    <button type="submit" class="ziifra-btn-primary w-full !rounded-xl">
        Continue
    </button>
</form>
@endsection

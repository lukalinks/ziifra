@extends('layouts.app')

@section('title', 'Leave types')
@section('header', 'Leave types')

@section('content')
<p class="mb-6">
    <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">← All settings</a>
</p>

<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Add leave type</h2>
        <form method="POST" action="{{ route('settings.leave-types.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">Name</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="e.g. Annual leave">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="default_days_per_year" class="block text-sm font-medium text-ziifra-ink">Days per year</label>
                <input id="default_days_per_year" name="default_days_per_year" type="number" min="0" max="365" step="0.5" required
                    value="{{ old('default_days_per_year', 20) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('default_days_per_year')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label class="flex items-center gap-2 text-sm text-ziifra-ink">
                    <input type="checkbox" name="is_paid" value="1" class="rounded border-ziifra-line" @checked(old('is_paid', true))>
                    Paid leave
                </label>
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                Add leave type
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Leave types</h2>
        @if ($leaveTypes->isEmpty())
            <p class="mt-4 text-sm text-ziifra-muted">No leave types yet.</p>
        @else
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($leaveTypes as $type)
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <div>
                            <span class="font-medium text-ziifra-ink">{{ $type->name }}</span>
                            <span class="mt-0.5 block text-ziifra-muted">
                                {{ number_format($type->default_days_per_year, 1) }} days/year
                                · {{ $type->is_paid ? 'Paid' : 'Unpaid' }}
                                · {{ $type->requests_count }} request(s)
                            </span>
                        </div>
                        <form method="POST" action="{{ route('settings.leave-types.destroy', $type) }}"
                            data-confirm="Remove this leave type?"
                            data-confirm-variant="danger"
                            data-confirm-accept="{{ __('common.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="shrink-0 text-red-600 hover:text-red-700">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Departments')
@section('header', 'Departments')

@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('settings.departments.add') }}</h2>
        <form method="POST" action="{{ route('settings.departments.store') }}" class="mt-4 space-y-4">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">Name</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                Add department
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Departments</h2>
        @if ($departments->isEmpty())
            <p class="mt-4 text-sm text-ziifra-muted">{{ __('settings.departments.empty') }}</p>
        @else
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($departments as $department)
                    <li class="flex items-center justify-between py-3 text-sm">
                        <span class="font-medium text-ziifra-ink">{{ $department->name }}</span>
                        <span class="text-ziifra-muted">{{ $department->employees_count }} employees</span>
                        <form method="POST" action="{{ route('settings.departments.destroy', $department) }}"
                            data-confirm="Remove this department? Employees in it will be unassigned."
                            data-confirm-variant="danger"
                            data-confirm-accept="{{ __('common.remove') }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-700">Remove</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</div>
<p class="mt-6">
    <a href="{{ route('settings.index') }}" class="text-sm text-ziifra-accent-deep hover:text-ziifra-accent-deep">← All settings</a>
</p>
@endsection

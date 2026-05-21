@extends('layouts.app')

@section('title', 'Custom fields')
@section('header', 'Custom fields')

@section('content')
<div class="grid gap-8 lg:grid-cols-2">
    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Add field definition</h2>
        <p class="mt-1 text-sm text-ziifra-muted">These fields appear on every employee form in your organization.</p>
        <form method="POST" action="{{ route('settings.employee-fields.store') }}" class="mt-4 space-y-4" id="field-definition-form">
            @csrf
            <div>
                <label for="name" class="block text-sm font-medium text-ziifra-ink">Field name</label>
                <input id="name" name="name" type="text" required value="{{ old('name') }}"
                    placeholder="e.g. National ID"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="type" class="block text-sm font-medium text-ziifra-ink">Type</label>
                <select id="type" name="type" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    @foreach ($types as $type)
                        <option value="{{ $type->value }}" @selected(old('type') === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @error('type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <div id="options-field" class="{{ old('type') === 'select' ? '' : 'hidden' }}">
                <label for="options" class="block text-sm font-medium text-ziifra-ink">Dropdown options (comma-separated)</label>
                <input id="options" name="options" type="text" value="{{ old('options') }}"
                    placeholder="Full-time, Part-time, Contract"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @error('options')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            <label class="flex items-center gap-2 text-sm text-ziifra-muted">
                <input type="checkbox" name="is_required" value="1" @checked(old('is_required'))
                    class="rounded border-ziifra-line text-ziifra-accent">
                Required when adding or editing employees
            </label>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                Add field
            </button>
        </form>
    </div>

    <div class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">Defined fields</h2>
        @if ($definitions->isEmpty())
            <p class="mt-4 text-sm text-ziifra-muted">{{ __('settings.employee_fields.empty') }}</p>
        @else
            <ul class="mt-4 divide-y divide-ziifra-line/60">
                @foreach ($definitions as $definition)
                    <li class="flex items-center justify-between gap-4 py-3 text-sm">
                        <div>
                            <span class="font-medium text-ziifra-ink">{{ $definition->name }}</span>
                            <span class="ml-2 text-ziifra-muted">{{ $definition->type->label() }}</span>
                            @if ($definition->is_required)
                                <span class="ml-1 text-xs text-red-600">required</span>
                            @endif
                            <p class="text-xs text-ziifra-muted">{{ $definition->values_count }} value(s) saved</p>
                        </div>
                        <form method="POST" action="{{ route('settings.employee-fields.destroy', $definition) }}"
                            data-confirm="Remove this field? Saved values will be deleted."
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

<script>
    document.getElementById('type')?.addEventListener('change', function () {
        document.getElementById('options-field')?.classList.toggle('hidden', this.value !== 'select');
    });
</script>
@endsection

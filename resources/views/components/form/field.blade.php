@props([
    'label',
    'name',
    'hint' => null,
    'required' => false,
])

<div {{ $attributes->class(['ziifra-form-field']) }}>
    <label for="{{ $name }}" class="ziifra-label-field">
        {{ $label }}
        @if ($required)
            <span class="text-red-600" aria-hidden="true">*</span>
        @endif
    </label>

    <div class="mt-1.5">
        {{ $slot }}
    </div>

    @if ($hint)
        <p class="mt-1 text-xs text-ziifra-muted">{{ $hint }}</p>
    @endif

    <x-form.error :name="$name" />
</div>

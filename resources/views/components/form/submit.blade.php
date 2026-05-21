@props([
    'label',
    'variant' => 'primary',
])

@php
    $class = match ($variant) {
        'outline' => 'ziifra-btn-app-outline w-full !rounded-xl !py-3',
        default => 'ziifra-btn-primary w-full !rounded-xl !py-3',
    };
@endphp

<button
    type="submit"
    {{ $attributes->class([$class, 'disabled:cursor-not-allowed disabled:opacity-60']) }}
    data-form-submit
    data-loading-text="{{ __('forms.submitting') }}"
>
    <span data-form-submit-label>{{ $label }}</span>
    <span data-form-submit-spinner class="hidden items-center justify-center gap-2">
        <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
        </svg>
        <span>{{ __('forms.submitting') }}</span>
    </span>
</button>

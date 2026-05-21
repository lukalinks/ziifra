@props([
    'variant' => 'dark',
    'href' => null,
    'class' => '',
    'showTagline' => false,
])

@php
    $onDark = $variant === 'light';
    $auto = $variant === 'auto';
    $logoHref = $href ?? route('home');
@endphp

<a href="{{ $logoHref }}" {{ $attributes->merge(['class' => 'ziifra-logo inline-flex items-center gap-2.5 group '.$class.($auto ? ' ziifra-logo-auto' : '')]) }}>
    <x-ziifra-logo-mark class="h-9 w-auto shrink-0 transition group-hover:scale-[1.03]" />
    <span @class([
        'ziifra-logo-word text-xl font-bold leading-none tracking-[0.04em]',
        'text-white' => $onDark,
        'text-ziifra-ink' => ! $onDark,
    ])>ZIIFRA</span>
    @if ($showTagline)
        <span @class([
            'ziifra-logo-tagline hidden text-[0.6rem] font-medium uppercase tracking-[0.22em] sm:block',
            'text-ziifra-muted' => $auto || ! $onDark,
            'text-white/50' => $onDark,
        ])>HR Platform</span>
    @endif
</a>

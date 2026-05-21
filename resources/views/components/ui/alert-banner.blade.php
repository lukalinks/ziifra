@props([
    'variant' => 'success',
    'title' => null,
    'dismissible' => true,
])

@php
    $variant = in_array($variant, ['success', 'danger', 'warning', 'info'], true) ? $variant : 'info';

    $role = in_array($variant, ['danger', 'warning'], true) ? 'alert' : 'status';

    $kindKey = match ($variant) {
        'success' => 'common.flash.kind_success',
        'danger' => 'common.flash.kind_danger',
        'warning' => 'common.flash.kind_warning',
        'info' => 'common.flash.kind_info',
        default => 'common.flash.kind_info',
    };

    $chipStyles = match ($variant) {
        'success' => 'border-teal-200/80 bg-teal-50/35 text-ziifra-accent-deep shadow-[inset_0_1px_0_rgb(255_255_255_0.7)]',
        'danger' => 'border-rose-200/90 bg-rose-50/45 text-rose-950 shadow-[inset_0_1px_0_rgb(255_255_255_0.65)]',
        'warning' => 'border-amber-200/90 bg-amber-50/50 text-amber-950 shadow-[inset_0_1px_0_rgb(255_255_255_0.65)]',
        'info' => 'border-sky-200/85 bg-sky-50/40 text-sky-950 shadow-[inset_0_1px_0_rgb(255_255_255_0.65)]',
        default => 'border-ziifra-line/90 bg-ziifra-cream/90 text-ziifra-accent-deep shadow-[inset_0_1px_0_rgb(255_255_255_0.65)]',
    };

    $icons = [
        'success' => '<path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />',
        'danger' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />',
        'warning' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
        'info' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12 15.75h.008v.008H12v-.008z" />',
    ];
@endphp

<div
    {{ $attributes->class([
        'ziifra-flash-banner relative',
        'ziifra-flash-banner--'.$variant,
    ]) }}
    data-alert-banner
    role="{{ $role }}"
    aria-live="polite"
>
    <div class="ziifra-flash-banner__accent" aria-hidden="true"></div>

    <div class="relative flex w-full min-w-0 gap-4 py-4 pl-6 pr-3">
        <div class="ziifra-flash-banner__glyph" aria-hidden="true">
            <span class="ziifra-flash-banner__glyph-frame"></span>
            <svg class="relative h-[1.125rem] w-[1.125rem]" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                {!! $icons[$variant] !!}
            </svg>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0 flex-1 space-y-2">
                    <p class="inline-flex items-center gap-2">
                        <span @class([
                            'rounded px-2 py-0.5 font-mono text-[0.625rem] font-semibold uppercase tracking-[0.22em]',
                            $chipStyles,
                        ])>
                            {{ __($kindKey) }}
                        </span>
                    </p>

                    @if (filled($title))
                        <p class="ziifra-display text-base font-semibold leading-snug tracking-tight text-ziifra-ink">
                            {{ $title }}
                        </p>
                    @endif

                    <div class="text-sm leading-relaxed text-ziifra-ink-soft [&_p+p]:mt-2">
                        {{ $slot }}
                    </div>
                </div>

                @if ($dismissible)
                    <button
                        type="button"
                        @class([
                            'group shrink-0 rounded-full border bg-ziifra-paper/70 p-2 shadow-sm backdrop-blur-sm transition',
                            'border-ziifra-line/90 text-ziifra-muted hover:border-ziifra-accent/35 hover:text-ziifra-accent-deep' => $variant === 'success' || $variant === 'info',
                            'border-rose-200/80 text-rose-700 hover:border-rose-400/60 hover:bg-rose-50/80 hover:text-rose-950' => $variant === 'danger',
                            'border-amber-200/85 text-amber-800 hover:border-amber-400/55 hover:bg-amber-50/90 hover:text-amber-950' => $variant === 'warning',
                        ])
                        aria-label="{{ __('common.flash.dismiss') }}"
                        onclick="this.closest('[data-alert-banner]').remove()"
                    >
                        <svg class="h-4 w-4 transition group-hover:rotate-90" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>

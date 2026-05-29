@extends('layouts.marketing')

@section('title', __('landing.title'))
@section('meta_description', __('landing.meta_description'))

@section('content')
@php
    $mockup = __('landing.mockup');
@endphp
{{-- Hero --}}
<section class="ziifra-landing-hero ziifra-diagonal-cut">
    <div class="ziifra-landing-glow -left-24 top-20 h-72 w-72 bg-ziifra-accent/25" aria-hidden="true"></div>
    <div class="ziifra-landing-glow right-0 top-1/3 h-96 w-96 bg-ziifra-copper/15" style="animation-delay: -3s" aria-hidden="true"></div>
    <div class="ziifra-grid-pattern pointer-events-none absolute inset-0 opacity-60" aria-hidden="true"></div>

    <div class="relative mx-auto flex w-full max-w-7xl flex-col justify-center px-4 pb-12 pt-6 sm:px-6 sm:pb-20 sm:pt-10 lg:min-h-[calc(100vh-4.5rem)] lg:px-8 lg:pb-28 lg:pt-16">
        <div class="grid items-center gap-6 sm:gap-10 lg:grid-cols-2 lg:gap-10 xl:gap-12">
            <div class="ziifra-hero-copy">
                <h1 class="ziifra-hero-eyebrow">{{ __('landing.hero.eyebrow') }}</h1>
                <p class="ziifra-hero-tagline mt-3 text-lg font-semibold leading-snug sm:mt-4 sm:text-2xl lg:text-[1.65rem]">
                    <span class="text-[#84cc16]">{{ __('landing.hero.tagline_connected') }}</span>
                    <span class="text-[#60a5fa]">{{ __('landing.hero.tagline_built') }}</span>
                </p>
                <p class="ziifra-hero-lead mt-4 text-[0.9375rem] leading-relaxed text-pretty text-white/65 sm:mt-6 sm:text-lg lg:max-w-xl">
                    {{ __('landing.hero.lead') }}
                </p>
                <div class="ziifra-hero-actions mt-6 sm:mt-8">
                    <a href="mailto:support@ziifra.com?subject=Book%20a%20demo" class="ziifra-btn-primary ziifra-hero-cta-primary shadow-lg shadow-ziifra-accent/20">{{ __('landing.hero.demo') }}</a>
                    <a href="#features" class="ziifra-hero-link">{{ __('landing.hero.explore') }}</a>
                </div>
            </div>

            <div class="ziifra-landing-hero-visual relative min-w-0">
                <div class="pointer-events-none absolute -top-6 right-0 hidden h-32 w-32 max-w-[50%] rounded-full bg-ziifra-accent/25 blur-3xl min-[640px]:block sm:-top-8 sm:h-40 sm:w-40 lg:h-48 lg:w-48" aria-hidden="true"></div>
                <div class="ziifra-landing-mockup-stage">
                <div class="ziifra-landing-mockup ziifra-landing-mockup-desktop relative" role="img" aria-label="{{ __('landing.hero.mockup_aria') }}">
                    <div class="ziifra-card relative overflow-hidden !rounded-2xl !border-white/20 !bg-ziifra-paper/95 p-0.5 shadow-2xl shadow-black/20 ring-2 ring-ziifra-accent/25 sm:!rounded-3xl sm:p-1">
                        {{-- Browser chrome --}}
                        <div class="flex items-center gap-1.5 rounded-t-[0.9rem] border-b border-ziifra-line/60 bg-gradient-to-r from-ziifra-cream to-ziifra-paper px-2.5 py-2 sm:gap-2 sm:rounded-t-2xl sm:px-4 sm:py-3">
                            <span class="h-2 w-2 rounded-full bg-red-400/90 sm:h-2.5 sm:w-2.5"></span>
                            <span class="h-2 w-2 rounded-full bg-amber-400/90 sm:h-2.5 sm:w-2.5"></span>
                            <span class="h-2 w-2 rounded-full bg-emerald-400/90 sm:h-2.5 sm:w-2.5"></span>
                            <span class="ml-1 min-w-0 flex-1 truncate rounded-md border border-ziifra-line/50 bg-ziifra-surface px-2 py-0.5 font-mono text-[0.55rem] text-ziifra-muted sm:ml-2 sm:px-3 sm:py-1 sm:text-[0.65rem]">
                                <span class="sm:hidden">demo.ziifra.com</span>
                                <span class="hidden sm:inline">demo-corp.ziifra.com/payroll</span>
                            </span>
                        </div>

                        <div class="flex">
                            {{-- Sidebar --}}
                            <div class="hidden w-[7.5rem] shrink-0 border-r border-ziifra-line/60 bg-ziifra-cream/80 sm:block lg:w-36">
                                <div class="border-b border-ziifra-line/60 px-3 py-2.5">
                                    <p class="truncate text-[0.65rem] font-semibold text-ziifra-ink">{{ $mockup['company'] }}</p>
                                </div>
                                <nav class="p-2.5" aria-hidden="true">
                                    <p class="px-2 font-mono text-[0.5rem] uppercase tracking-widest text-ziifra-muted">{{ $mockup['sections']['people'] }}</p>
                                    <ul class="mt-1.5 space-y-0.5 text-[0.65rem] font-medium lg:text-[0.7rem]">
                                        @foreach($mockup['nav_primary'] as $navItem)
                                            <li @class([
                                                'rounded-md px-2 py-1.5',
                                                'bg-ziifra-accent/15 text-ziifra-accent-deep' => $navItem['active'],
                                                'text-ziifra-muted' => ! $navItem['active'],
                                            ])>{{ $navItem['label'] }}</li>
                                        @endforeach
                                    </ul>
                                    <p class="mt-3 px-2 font-mono text-[0.5rem] uppercase tracking-widest text-ziifra-muted">{{ $mockup['sections']['operations'] }}</p>
                                    <ul class="mt-1.5 space-y-0.5 text-[0.65rem] font-medium text-ziifra-muted lg:text-[0.7rem]">
                                        @foreach($mockup['nav_secondary'] as $label)
                                            <li class="rounded-md px-2 py-1.5">{{ $label }}</li>
                                        @endforeach
                                    </ul>
                                </nav>
                            </div>

                            {{-- Main content --}}
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between border-b border-ziifra-line/60 bg-ziifra-paper px-3 py-2 sm:px-4">
                                    <p class="text-[0.7rem] font-medium text-ziifra-muted sm:text-xs">{{ $mockup['payroll_title'] }}</p>
                                    <span class="hidden text-[0.65rem] text-ziifra-muted sm:inline">Arben K.</span>
                                </div>

                                <div class="space-y-3 p-3 sm:p-4">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <div>
                                            <p class="font-mono text-[0.55rem] uppercase tracking-widest text-ziifra-muted sm:text-[0.6rem]">{{ $mockup['payroll_run'] }}</p>
                                            <p class="mt-0.5 text-sm font-semibold text-ziifra-ink sm:text-base">{{ $mockup['payroll_company'] }}</p>
                                        </div>
                                        <span class="rounded-full bg-emerald-500/15 px-2.5 py-1 text-[0.6rem] font-semibold text-emerald-300 sm:text-[0.65rem]">{{ $mockup['status_locked'] }}</span>
                                    </div>

                                    <div class="grid grid-cols-3 gap-1.5 sm:gap-2">
                                        <div class="ziifra-landing-mockup-stat rounded-lg border border-ziifra-line/80 bg-ziifra-cream p-2 sm:p-2.5">
                                            <p class="text-[0.5rem] text-ziifra-muted sm:text-[0.6rem]">{{ $mockup['stats']['employees'] }}</p>
                                            <p class="ziifra-landing-mockup-stat-value mt-0.5 text-base font-semibold tabular-nums text-ziifra-ink sm:text-lg">24</p>
                                        </div>
                                        <div class="ziifra-landing-mockup-stat rounded-lg border border-ziifra-accent/20 bg-gradient-to-br from-ziifra-accent/12 to-transparent p-2 sm:p-2.5">
                                            <p class="text-[0.5rem] text-ziifra-muted sm:text-[0.6rem]">{{ $mockup['stats']['net_pay'] }}</p>
                                            <p class="ziifra-landing-mockup-stat-value mt-0.5 text-base font-semibold tabular-nums text-ziifra-accent-deep sm:text-lg">€18.4k</p>
                                        </div>
                                        <div class="ziifra-landing-mockup-stat rounded-lg border border-ziifra-line/80 bg-ziifra-cream p-2 sm:p-2.5">
                                            <p class="text-[0.5rem] text-ziifra-muted sm:text-[0.6rem]">{{ $mockup['stats']['on_leave'] }}</p>
                                            <p class="ziifra-landing-mockup-stat-value mt-0.5 text-base font-semibold tabular-nums text-ziifra-ink sm:text-lg">3</p>
                                        </div>
                                    </div>

                                    {{-- Payroll table (hidden on small phones — stats tell the story) --}}
                                    <div class="ziifra-landing-mockup-table-wrap hidden min-[480px]:block">
                                    <div class="ziifra-landing-mockup-table min-w-[14rem] overflow-hidden rounded-xl border border-ziifra-line/80 sm:min-w-0">
                                        <div class="grid grid-cols-[1fr_auto] gap-2 border-b border-ziifra-line/60 bg-ziifra-cream px-2.5 py-1.5 font-mono text-[0.55rem] uppercase tracking-wider text-ziifra-muted sm:grid-cols-4 sm:px-3 sm:py-2 sm:text-[0.6rem]">
                                            <span>{{ $mockup['table']['employee'] }}</span>
                                            <span class="hidden sm:block">{{ $mockup['table']['department'] }}</span>
                                            <span class="hidden sm:block">{{ $mockup['table']['gross'] }}</span>
                                            <span class="text-right">{{ $mockup['table']['net'] }}</span>
                                        </div>
                                        <ul class="divide-y divide-ziifra-line/50">
                                            @foreach($mockup['rows'] as $row)
                                                <li class="grid grid-cols-[1fr_auto] items-center gap-2 px-2.5 py-2 text-[0.65rem] sm:grid-cols-4 sm:px-3 sm:text-xs">
                                                    <span class="flex min-w-0 items-center gap-2">
                                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-ziifra-accent/15 text-[0.55rem] font-bold text-ziifra-accent-deep">{{ $row['initials'] }}</span>
                                                        <span class="truncate font-medium text-ziifra-ink">{{ $row['name'] }}</span>
                                                    </span>
                                                    <span class="hidden truncate text-ziifra-muted sm:block">{{ $row['department'] }}</span>
                                                    <span class="hidden tabular-nums text-ziifra-muted sm:block">{{ $row['gross'] }}</span>
                                                    <span class="text-right font-medium tabular-nums text-ziifra-ink">{{ $row['net'] }}</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    </div>

                                    <div class="ziifra-landing-mockup-chips hidden flex-wrap gap-1 min-[480px]:flex sm:gap-1.5">
                                        @foreach($mockup['chips'] as $chip)
                                            <span class="ziifra-module-chip">{{ $chip }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Mobile app preview --}}
                <div class="ziifra-landing-mockup-phone" aria-hidden="true">
                    <div class="ziifra-landing-mockup-phone-shell overflow-hidden rounded-[1.75rem] border-[3px] border-white/25 bg-ziifra-night shadow-2xl shadow-black/40 ring-1 ring-white/10 min-[640px]:rounded-[1.75rem]">
                        <div class="flex items-center justify-between bg-ziifra-night px-3 pb-1 pt-2 sm:px-3">
                            <span class="font-mono text-[0.5rem] text-white/70 min-[640px]:text-[0.45rem]">9:41</span>
                            <span class="mx-auto h-1 w-8 rounded-full bg-white/20"></span>
                            <span class="flex gap-0.5">
                                <span class="h-1.5 w-2.5 rounded-sm border border-white/40"></span>
                            </span>
                        </div>
                        <div class="bg-ziifra-cream px-3.5 pb-4 pt-2.5 sm:px-3 sm:pb-3 sm:pt-2">
                            <p class="font-mono text-[0.5rem] uppercase tracking-widest text-ziifra-muted min-[640px]:text-[0.45rem]">{{ $mockup['phone']['today'] }}</p>
                            <p class="mt-1 text-sm font-semibold text-ziifra-ink min-[640px]:text-[0.7rem]">{{ $mockup['phone']['greeting'] }}</p>
                            <div class="mt-3 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-3 min-[640px]:mt-2.5 min-[640px]:p-2.5">
                                <p class="text-[0.5rem] text-ziifra-muted min-[640px]:text-[0.45rem]">{{ $mockup['phone']['leave_label'] }}</p>
                                <p class="mt-0.5 text-base font-semibold tabular-nums text-ziifra-accent-deep min-[640px]:text-sm">{{ $mockup['phone']['leave_days'] }}</p>
                                <p class="mt-1 text-[0.5rem] text-ziifra-muted min-[640px]:text-[0.45rem]">{{ $mockup['phone']['leave_pending'] }}</p>
                            </div>
                            <div class="mt-3 space-y-2 min-[640px]:mt-2 min-[640px]:space-y-1.5">
                                <div class="rounded-lg bg-ziifra-accent px-2.5 py-2 text-center text-xs font-semibold text-ziifra-ink min-[640px]:py-1.5 min-[640px]:text-[0.55rem]">{{ $mockup['phone']['btn_request_leave'] }}</div>
                                <div class="rounded-lg border border-ziifra-line/80 bg-ziifra-paper px-2.5 py-2 text-center text-xs font-medium text-ziifra-muted min-[640px]:py-1.5 min-[640px]:text-[0.55rem]">{{ $mockup['phone']['btn_calendar'] }}</div>
                            </div>
                            <ul class="mt-3 space-y-1.5 min-[640px]:mt-2.5 min-[640px]:space-y-1">
                                @foreach($mockup['phone']['activities'] as $activity)
                                    <li class="flex items-center justify-between rounded-lg border border-ziifra-line/60 bg-ziifra-paper px-2.5 py-2 min-[640px]:px-2 min-[640px]:py-1.5">
                                        <span class="text-xs font-medium text-ziifra-ink min-[640px]:text-[0.5rem]">{{ $activity['label'] }}</span>
                                        <span class="text-[0.625rem] text-ziifra-muted min-[640px]:text-[0.45rem]">{{ $activity['detail'] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="ziifra-landing-mockup-mobile-stats" aria-hidden="true">
                    <div class="ziifra-landing-mockup-mobile-stat">
                        <p class="ziifra-landing-mockup-mobile-stat-value">24</p>
                        <p class="ziifra-landing-mockup-mobile-stat-label">{{ $mockup['stats']['employees'] }}</p>
                    </div>
                    <div class="ziifra-landing-mockup-mobile-stat ziifra-landing-mockup-mobile-stat--accent">
                        <p class="ziifra-landing-mockup-mobile-stat-value">€18.4k</p>
                        <p class="ziifra-landing-mockup-mobile-stat-label">{{ $mockup['stats']['net_pay'] }}</p>
                    </div>
                    <div class="ziifra-landing-mockup-mobile-stat">
                        <p class="ziifra-landing-mockup-mobile-stat-value">3</p>
                        <p class="ziifra-landing-mockup-mobile-stat-label">{{ $mockup['stats']['on_leave'] }}</p>
                    </div>
                </div>
                </div>
            </div>
        </div>
    </div>
</section>

{{-- Capabilities --}}
<section id="features" class="ziifra-landing-section ziifra-features-section relative -mt-4 overflow-hidden pt-20 sm:-mt-10 sm:pt-32 lg:-mt-14 lg:pt-40">
    <div class="pointer-events-none absolute inset-0 ziifra-grid-pattern opacity-[0.35]" aria-hidden="true"></div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="ziifra-landing-section-head">
            <p class="ziifra-label justify-center">{{ __('landing.features.label') }}</p>
            <h2 class="ziifra-display mt-3 text-2xl font-semibold text-ziifra-ink sm:mt-4 sm:text-4xl">{{ __('landing.features.title') }}</h2>
            <p class="mt-3 mx-auto max-w-2xl text-base leading-relaxed text-ziifra-muted sm:mt-4 sm:text-lg">{{ __('landing.features.subtitle') }}</p>
        </div>

        <ul class="ziifra-capability-grid mt-10 sm:mt-14 lg:mt-16">
            @foreach(__('landing.features.items') as $index => $item)
                <li>
                    <article @class(['ziifra-capability-card', 'ziifra-capability-card-featured' => $index === 0])>
                        <div class="ziifra-capability-card-icon" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="{{ $item['icon'] ?? '' }}" />
                            </svg>
                        </div>
                        <h3 class="ziifra-capability-card-title">{{ $item['title'] }}</h3>
                        <p class="ziifra-capability-card-desc">{{ $item['desc'] }}</p>
                    </article>
                </li>
            @endforeach
        </ul>
    </div>
</section>

{{-- Why --}}
<section class="ziifra-landing-section ziifra-landing-section--muted">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-10 lg:grid-cols-2 lg:items-start lg:gap-16">
            <div class="ziifra-card-ink relative overflow-hidden p-6 sm:p-8 lg:p-10">
                <div class="pointer-events-none absolute -right-12 -top-12 h-40 w-40 rounded-full bg-ziifra-accent/20 blur-3xl" aria-hidden="true"></div>
                <p class="ziifra-label !text-ziifra-accent-glow before:!bg-ziifra-accent-glow">{{ __('landing.compliance.label') }}</p>
                <h3 class="mt-4 text-2xl font-semibold">{{ __('landing.compliance.title') }}</h3>
                <p class="mt-3 text-sm leading-relaxed text-white/55">{{ __('landing.compliance.subtitle') }}</p>
                <div class="mt-10 grid grid-cols-1 gap-3 border-t border-white/10 pt-8 sm:grid-cols-3">
                    @foreach(['XK' => __('landing.compliance.country'), 'EUR' => __('landing.compliance.currency'), '6' => __('landing.compliance.languages')] as $code => $name)
                        <div class="rounded-xl border border-white/10 bg-white/5 p-3 text-center">
                            <p class="font-mono text-lg font-bold text-ziifra-accent-glow">{{ $code }}</p>
                            <p class="mt-1 text-[0.65rem] uppercase tracking-wider text-white/45">{{ $name }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="lg:pt-4">
                <p class="ziifra-label">{{ __('landing.why.label') }}</p>
                <h2 class="ziifra-display mt-3 text-2xl font-semibold text-ziifra-ink sm:mt-4 sm:text-4xl">
                    {{ __('landing.why.title') }}
                </h2>
                <p class="mt-4 text-base leading-relaxed text-ziifra-muted sm:mt-5 sm:text-lg">
                    {{ __('landing.why.subtitle') }}
                </p>
                <ul class="mt-8 space-y-6 sm:mt-12 sm:space-y-8">
                    @foreach(__('landing.why.items') as $index => $item)
                        @php $num = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); @endphp
                        <li class="flex gap-5 border-l-2 border-ziifra-line pl-6 transition hover:border-ziifra-accent">
                            <span class="font-mono text-sm font-semibold text-ziifra-accent-deep">{{ $num }}</span>
                            <div>
                                <p class="font-semibold text-ziifra-ink">{{ $item['title'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-ziifra-muted">{{ $item['text'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Steps --}}
<section class="ziifra-landing-section ziifra-landing-section--surface">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="grid gap-8 lg:grid-cols-2 lg:gap-16">
            <div class="lg:sticky lg:top-28 lg:self-start">
                <p class="ziifra-label">{{ __('landing.steps.label') }}</p>
                <h2 class="ziifra-display mt-3 text-2xl font-semibold text-ziifra-ink sm:mt-4 sm:text-4xl">{{ __('landing.steps.title') }}</h2>
                <p class="mt-3 max-w-md text-sm leading-relaxed text-ziifra-muted sm:mt-4 sm:text-base">{{ __('landing.steps.subtitle') }}</p>
                <ol class="mt-8 space-y-0 sm:mt-10">
                    @foreach(__('landing.steps.list') as $index => $stepItem)
                        @php $step = str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT); @endphp
                        <li class="relative flex gap-4 border-l border-ziifra-line py-6 pl-6 last:pb-0 sm:gap-6 sm:py-8 sm:pl-8">
                            <span class="absolute -left-3 top-8 flex h-6 w-6 items-center justify-center rounded-full border-2 border-ziifra-paper bg-ziifra-accent text-[0.6rem] font-bold text-ziifra-ink">{{ $step }}</span>
                            <div>
                                <p class="font-semibold text-ziifra-ink">{{ $stepItem['title'] }}</p>
                                <p class="mt-1 text-sm text-ziifra-muted">{{ $stepItem['desc'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ol>
            </div>
            <div class="ziifra-landing-steps-card flex flex-col justify-center p-6 sm:p-8">
                <p class="font-mono text-xs uppercase tracking-widest text-ziifra-accent-deep">{{ __('landing.steps.included_label') }}</p>
                <ul class="mt-8 grid gap-3 sm:grid-cols-2">
                    @foreach(__('landing.steps.included') as $item)
                        <li class="flex items-center gap-3 rounded-xl border border-ziifra-line/80 bg-ziifra-cream/70 px-4 py-3 text-sm font-medium text-ziifra-ink">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-ziifra-accent"></span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- Pricing --}}
<section id="pricing" class="ziifra-landing-section border-t border-ziifra-line bg-ziifra-cream">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="ziifra-landing-section-head">
            <p class="ziifra-label justify-center">{{ __('landing.pricing.label') }}</p>
            <h2 class="ziifra-display mt-3 text-2xl font-semibold text-ziifra-ink sm:mt-4 sm:text-4xl">{{ __('landing.pricing.title') }}</h2>
            <p class="mt-3 text-base text-ziifra-muted sm:mt-4 sm:text-lg">{{ __('landing.pricing.subtitle', ['days' => $trialDays]) }}</p>
        </div>

        <div class="ziifra-landing-pricing-grid">
            @php
                $starter = $pricingPlans['starter'];
                $pro = $pricingPlans['pro'];
            @endphp
            <div class="ziifra-card ziifra-pricing-card flex flex-col p-6 sm:p-8 lg:p-10">
                <p class="font-mono text-xs uppercase tracking-widest text-ziifra-muted">{{ __('landing.pricing.plans.starter') }}</p>
                <p class="mt-4 flex items-baseline gap-1">
                    @if ($starter['monthly_price'])
                        <span class="text-4xl font-semibold tabular-nums text-ziifra-ink sm:text-5xl">€{{ $starter['monthly_price'] }}</span>
                        <span class="text-ziifra-muted">{{ __('landing.pricing.per_month') }}</span>
                    @else
                        <span class="text-3xl font-semibold text-ziifra-ink">{{ $starter['price_label'] }}</span>
                    @endif
                </p>
                <p class="mt-2 text-sm text-ziifra-muted">
                    @if ($starter['employee_limit'])
                        {{ __('landing.pricing.up_to_employees', ['count' => $starter['employee_limit']]) }}
                    @else
                        {{ __('landing.pricing.unlimited_employees') }}
                    @endif
                </p>
                <ul class="mt-8 flex-1 space-y-3 border-t border-ziifra-line pt-8 text-sm text-ziifra-muted">
                    @foreach ($starter['features'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="h-1 w-4 rounded-full bg-ziifra-accent"></span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="ziifra-btn-secondary mt-8 w-full text-center">{{ __('landing.pricing.get_started') }}</a>
            </div>

            <div class="ziifra-pricing-pro flex flex-col p-6 sm:p-8 lg:p-10">
                <span class="self-start rounded-full bg-ziifra-accent/20 px-3 py-1 font-mono text-[0.65rem] uppercase tracking-wider text-ziifra-accent-glow">{{ __('landing.pricing.most_popular') }}</span>
                <p class="mt-6 font-mono text-xs uppercase tracking-widest text-white/50">{{ __('landing.pricing.plans.pro') }}</p>
                <p class="mt-4 flex items-baseline gap-1">
                    @if ($pro['monthly_price'])
                        <span class="text-4xl font-semibold tabular-nums sm:text-5xl">€{{ $pro['monthly_price'] }}</span>
                        <span class="text-white/45">{{ __('landing.pricing.per_month') }}</span>
                    @else
                        <span class="text-3xl font-semibold">{{ $pro['price_label'] }}</span>
                    @endif
                </p>
                <p class="mt-2 text-sm text-white/55">
                    @if ($pro['employee_limit'])
                        {{ __('landing.pricing.up_to_employees', ['count' => $pro['employee_limit']]) }}
                    @else
                        {{ __('landing.pricing.unlimited_employees') }}
                    @endif
                    @if ($pro['payroll'])
                        {{ __('landing.pricing.payroll_included') }}
                    @endif
                </p>
                <ul class="mt-8 flex-1 space-y-3 border-t border-white/10 pt-8 text-sm text-white/60">
                    @foreach ($pro['features'] as $item)
                        <li class="flex items-center gap-3">
                            <span class="h-1 w-4 rounded-full bg-ziifra-accent-glow"></span>
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
                <a href="{{ route('register') }}" class="ziifra-btn-primary mt-8 w-full text-center">{{ __('landing.pricing.start_trial') }}</a>
            </div>
        </div>
    </div>
</section>

{{-- FAQ --}}
<section id="faq" class="ziifra-landing-section">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="ziifra-landing-section-head">
            <p class="ziifra-label justify-center">{{ __('landing.faq.label') }}</p>
            <h2 class="ziifra-display mt-3 text-2xl font-semibold text-ziifra-ink sm:mt-4 sm:text-3xl">{{ __('landing.faq.title') }}</h2>
        </div>
        <dl class="ziifra-faq mt-8 space-y-3 sm:mt-14 sm:space-y-4">
            @foreach(__('landing.faq.items') as $faq)
                <details class="px-4 py-4 sm:px-6 sm:py-5">
                    <summary>{{ $faq['q'] }}</summary>
                    <dd class="mt-4 border-t border-ziifra-line/80 pt-4 text-sm leading-relaxed text-ziifra-muted">{{ $faq['a'] }}</dd>
                </details>
            @endforeach
        </dl>
    </div>
</section>

{{-- CTA --}}
<section class="ziifra-landing-section pb-16 sm:pb-28 lg:pb-32">
    <div class="relative mx-auto max-w-5xl px-4 sm:px-6">
        <div class="ziifra-landing-cta">
            <div class="ziifra-cta-ring" aria-hidden="true"></div>
            <div class="pointer-events-none absolute inset-0 ziifra-grid-pattern opacity-25" aria-hidden="true"></div>
            <div class="relative px-5 text-center sm:px-10">
                <p class="ziifra-label justify-center !text-ziifra-accent-glow before:!bg-ziifra-accent-glow">{{ __('landing.cta.label') }}</p>
                <h2 class="ziifra-display mt-4 text-2xl font-semibold text-white sm:mt-6 sm:text-4xl">
                    {{ __('landing.cta.title') }}
                </h2>
                <p class="mt-4 text-base text-white/55 sm:mt-5 sm:text-lg">{{ __('landing.cta.subtitle', ['days' => $trialDays]) }}</p>
                <div class="ziifra-landing-cta-actions mt-8 sm:mt-10">
                    <a href="{{ route('register') }}" class="ziifra-btn-primary">{{ __('landing.cta.primary') }}</a>
                    <a href="{{ route('login') }}" class="ziifra-btn-ghost-light">{{ __('landing.cta.login') }}</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection

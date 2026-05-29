@extends('admin.layout')

@section('title', __('admin.billing.heading'))

@section('content')
<div class="mb-8">
    <h1 class="text-2xl font-semibold text-slate-900">{{ __('admin.billing.heading') }}</h1>
    <p class="mt-2 max-w-2xl text-sm text-slate-600">{{ __('admin.billing.subtitle') }}</p>
</div>

<form method="POST" action="{{ route('admin.billing.update') }}" class="space-y-8">
    @csrf
    @method('PUT')

    <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.billing.trial_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('admin.billing.trial_help') }}</p>

        <div class="mt-4 max-w-xs">
            <label for="trial_days" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.trial_days') }}</label>
            <input id="trial_days" name="trial_days" type="number" min="1" max="90"
                value="{{ old('trial_days', $trialDays) }}"
                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
            @error('trial_days')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    </section>

    <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6 shadow-sm">
        <h2 class="text-sm font-semibold uppercase tracking-wide text-slate-500">{{ __('admin.billing.plans_heading') }}</h2>
        <p class="mt-1 text-sm text-slate-600">{{ __('admin.billing.plans_help') }}</p>
    </section>

    <div class="grid gap-6 xl:grid-cols-2">
        @foreach ($planKeys as $planKey)
            @php
                $plan = $plans[$planKey];
                $isEnterprise = $planKey === 'enterprise';
                $isTrial = $planKey === 'trial';
                $enabledFeatures = old('plans.'.$planKey.'.enabled_features', $plan['enabled_features'] ?? []);
                if (! is_array($enabledFeatures)) {
                    $enabledFeatures = [];
                }
            @endphp
            <section class="rounded-xl border border-slate-200 bg-ziifra-paper p-6 shadow-sm">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold text-slate-900">{{ ucfirst($planKey) }}</h2>
                    <span class="rounded-full bg-slate-100 px-3 py-1 font-mono text-xs uppercase tracking-wide text-slate-600">{{ $planKey }}</span>
                </div>

                <div class="mt-6 space-y-4">
                    <div>
                        <label for="plans_{{ $planKey }}_name" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.name') }}</label>
                        <input id="plans_{{ $planKey }}_name" name="plans[{{ $planKey }}][name]" type="text"
                            value="{{ old('plans.'.$planKey.'.name', $plan['name']) }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        @error('plans.'.$planKey.'.name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="plans_{{ $planKey }}_monthly_price" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.monthly_price') }}</label>
                            <input id="plans_{{ $planKey }}_monthly_price" name="plans[{{ $planKey }}][monthly_price]" type="number" min="0" step="0.01"
                                value="{{ old('plans.'.$planKey.'.monthly_price', $plan['monthly_price']) }}"
                                @disabled($isTrial || $isEnterprise)
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 disabled:bg-slate-100 disabled:text-slate-500">
                            @error('plans.'.$planKey.'.monthly_price')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="plans_{{ $planKey }}_employee_limit" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.employee_limit') }}</label>
                            <input id="plans_{{ $planKey }}_employee_limit" name="plans[{{ $planKey }}][employee_limit]" type="number" min="1"
                                value="{{ old('plans.'.$planKey.'.employee_limit', $plan['employee_limit']) }}"
                                @disabled($isEnterprise)
                                placeholder="{{ $isEnterprise ? __('admin.billing.unlimited') : '' }}"
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900 disabled:bg-slate-100 disabled:text-slate-500">
                            @error('plans.'.$planKey.'.employee_limit')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div>
                        <label for="plans_{{ $planKey }}_price_label" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.price_label') }}</label>
                        <input id="plans_{{ $planKey }}_price_label" name="plans[{{ $planKey }}][price_label]" type="text"
                            value="{{ old('plans.'.$planKey.'.price_label', $plan['price_label']) }}"
                            class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                        @error('plans.'.$planKey.'.price_label')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div>
                        <p class="block text-sm font-medium text-slate-700">{{ __('admin.billing.plan_features') }}</p>
                        <p class="mt-1 text-xs text-slate-500">{{ __('admin.billing.plan_features_help') }}</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-2">
                            @foreach ($featureCatalog as $feature)
                                @php
                                    $checked = in_array($feature['key'], $enabledFeatures, true)
                                        || ($isEnterprise && $feature['required']);
                                @endphp
                                <label @class([
                                    'flex items-start gap-2 rounded-lg border px-3 py-2 text-sm',
                                    $isEnterprise ? 'border-slate-200 bg-slate-50 text-slate-600' : 'border-slate-200 text-slate-700',
                                ])>
                                    @if ($isEnterprise || $feature['required'])
                                        <input type="checkbox" checked disabled class="mt-0.5 rounded border-slate-300 text-slate-900">
                                        <input type="hidden" name="plans[{{ $planKey }}][enabled_features][]" value="{{ $feature['key'] }}">
                                    @else
                                        <input type="checkbox"
                                            name="plans[{{ $planKey }}][enabled_features][]"
                                            value="{{ $feature['key'] }}"
                                            @checked($checked)
                                            class="mt-0.5 rounded border-slate-300 text-slate-900 focus:ring-slate-500">
                                    @endif
                                    <span>{{ $feature['label'] }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('plans.'.$planKey.'.enabled_features')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        @error('plans.'.$planKey.'.enabled_features.*')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    @if (in_array($planKey, ['starter', 'pro'], true))
                        <div>
                            <label for="plans_{{ $planKey }}_stripe_price_id" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.stripe_price_id') }}</label>
                            <input id="plans_{{ $planKey }}_stripe_price_id" name="plans[{{ $planKey }}][stripe_price_id]" type="text"
                                value="{{ old('plans.'.$planKey.'.stripe_price_id', $plan['stripe_price_id']) }}"
                                placeholder="price_..."
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.billing.stripe_price_help') }}</p>
                            @error('plans.'.$planKey.'.stripe_price_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label for="plans_{{ $planKey }}_paypal_plan_id" class="block text-sm font-medium text-slate-700">{{ __('admin.billing.paypal_plan_id') }}</label>
                            <input id="plans_{{ $planKey }}_paypal_plan_id" name="plans[{{ $planKey }}][paypal_plan_id]" type="text"
                                value="{{ old('plans.'.$planKey.'.paypal_plan_id', $plan['paypal_plan_id'] ?? null) }}"
                                placeholder="P-..."
                                class="mt-1 block w-full rounded-lg border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-slate-900 focus:outline-none focus:ring-1 focus:ring-slate-900">
                            <p class="mt-1 text-xs text-slate-500">{{ __('admin.billing.paypal_plan_help') }}</p>
                            @error('plans.'.$planKey.'.paypal_plan_id')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                        </div>
                    @endif
                </div>
            </section>
        @endforeach
    </div>

    <div class="flex gap-3">
        <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white hover:bg-slate-800">
            {{ __('admin.billing.save') }}
        </button>
    </div>
</form>
@endsection

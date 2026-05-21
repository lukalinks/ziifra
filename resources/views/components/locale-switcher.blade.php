@php
    $locales = app(\App\Services\LocaleConfigurationService::class)->enabledOptions();
    $current = app()->getLocale();
@endphp

@if (count($locales) > 1)
    <form method="POST" action="{{ route('locale.update') }}" class="inline-flex items-center">
        @csrf
        <label for="locale-switcher" class="sr-only">{{ __('locales.switch_label') }}</label>
        <select id="locale-switcher" name="locale" onchange="this.form.submit()"
            class="rounded-lg border border-ziifra-line/80 bg-ziifra-paper px-2 py-1.5 text-xs font-medium text-ziifra-ink focus:border-ziifra-accent focus:outline-none focus:ring-1 focus:ring-ziifra-accent/25">
            @foreach ($locales as $code => $label)
                <option value="{{ $code }}" @selected($current === $code)>{{ $label }}</option>
            @endforeach
        </select>
    </form>
@endif

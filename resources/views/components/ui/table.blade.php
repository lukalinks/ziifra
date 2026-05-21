@props([
    'caption' => null,
])

<div {{ $attributes->class(['overflow-x-auto rounded-xl border border-ziifra-line/80']) }}>
    <table class="ziifra-table min-w-full divide-y divide-ziifra-line/80 text-sm">
        @if ($caption)
            <caption class="sr-only">{{ $caption }}</caption>
        @endif
        @isset($head)
            <thead class="bg-ziifra-cream/50 text-left text-xs font-medium uppercase tracking-wide text-ziifra-muted">
                {{ $head }}
            </thead>
        @endisset
        <tbody class="divide-y divide-ziifra-line/60 bg-ziifra-paper/80">
            {{ $slot }}
        </tbody>
    </table>
</div>

<div class="mt-6 rounded-lg border border-dashed border-ziifra-line/80 bg-ziifra-cream/50 p-4">
    <p class="text-xs font-semibold uppercase tracking-wide text-ziifra-muted">{{ __('settings.company.payslip_preview') }}</p>
    <ul class="mt-3 space-y-1 text-sm text-ziifra-ink">
        @forelse ($organization->payslipLegalLines() as $line)
            <li>{{ $line }}</li>
        @empty
            <li class="text-ziifra-muted">{{ __('settings.company.payslip_preview_empty') }}</li>
        @endforelse
    </ul>
</div>

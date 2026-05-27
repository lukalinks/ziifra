<section class="ziifra-documents-side-card">
    <div class="ziifra-documents-side-card-head">
        <div>
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('project_documents.export_summary') }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ __('project_documents.export_hint') }}</p>
        </div>
    </div>

    <form method="GET" action="{{ route('project-documents.export') }}" class="ziifra-documents-side-card-body space-y-3">
        @if ($selectedProject)
            <input type="hidden" name="project_id" value="{{ $selectedProject->id }}">
        @endif
        <div>
            <label for="export_period_start" class="ziifra-documents-label">{{ __('project_documents.period_start') }}</label>
            <input type="date" id="export_period_start" name="period_start" required
                value="{{ request('period_start', $exportDefaults['period_start']) }}"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
        </div>
        <div>
            <label for="export_period_end" class="ziifra-documents-label">{{ __('project_documents.period_end') }}</label>
            <input type="date" id="export_period_end" name="period_end" required
                value="{{ request('period_end', $exportDefaults['period_end']) }}"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
        </div>
        <button type="submit" class="ziifra-btn-app-outline w-full !text-sm">{{ __('project_documents.download_csv') }}</button>
    </form>
</section>

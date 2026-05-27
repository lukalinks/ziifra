<section class="ziifra-documents-panel">
    <div class="ziifra-documents-panel-head">
        <div>
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('project_documents.recent_title') }}</h3>
            <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('project_documents.recent_subtitle') }}</p>
        </div>
        <a href="{{ route('project-documents.index', ['view' => 'all']) }}" data-page-nav class="text-sm font-medium text-ziifra-accent-deep hover:underline">
            {{ __('project_documents.view_all') }}
        </a>
    </div>

    <div class="divide-y divide-ziifra-line/60">
        @foreach ($recentDocuments as $document)
            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                <div class="min-w-0">
                    <p class="font-medium text-ziifra-ink">{{ $document->title }}</p>
                    <p class="mt-0.5 truncate text-xs text-ziifra-muted">
                        {{ $document->project->name }} · {{ $document->category->label() }} · {{ $document->uploaded_at->format('M j, Y') }}
                    </p>
                </div>
                <a href="{{ route('project-documents.download', $document) }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                    {{ __('documents.download') }}
                </a>
            </div>
        @endforeach
    </div>
</section>

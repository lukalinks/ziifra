@php
    $headerLabel = $selectedProject?->name ?? ($hasFilters ? __('project_documents.filtered_view') : __('project_documents.all_projects'));
    $headerHint = $selectedProject
        ? __('project_documents.project_header_hint')
        : __('project_documents.all_projects_hint');
    $fileCount = $documents instanceof \Illuminate\Contracts\Pagination\Paginator
        ? $documents->total()
        : $documents->count();
@endphp

<div class="ziifra-documents-folder-head">
    <div class="flex min-w-0 flex-1 items-start gap-4">
        <span @class(['ziifra-documents-folder-icon', $selectedProject ? 'ziifra-doc-folder-custom' : 'ziifra-doc-folder-muted']) aria-hidden="true">
            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
        </span>
        <div class="min-w-0">
            <h2 class="truncate text-lg font-semibold tracking-tight text-ziifra-ink sm:text-xl">{{ $headerLabel }}</h2>
            <p class="mt-1 text-sm text-ziifra-muted">{{ $headerHint }}</p>
            <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ziifra-cream/80 px-2.5 py-0.5 text-xs font-medium tabular-nums text-ziifra-muted ring-1 ring-ziifra-line/70">
                {{ __('documents.folders.items', ['count' => $fileCount]) }}
            </p>
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <a href="{{ route('project-documents.index') }}" data-page-nav class="ziifra-btn-app-outline !text-sm">
            {{ __('project_documents.back_to_library') }}
        </a>
        @if ($selectedProject)
            <a href="{{ route('projects.show', $selectedProject) }}" data-page-nav class="ziifra-btn-app-outline !text-sm">
                {{ __('project_documents.open_project') }}
            </a>
        @endif
        @if ($canManage)
            <a href="#project-document-upload" class="ziifra-btn-primary !text-sm">
                {{ __('project_documents.upload') }}
            </a>
        @endif
    </div>
</div>

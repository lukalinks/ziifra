@php
    $projectTones = ['accent', 'sky', 'amber', 'custom', 'muted'];
@endphp

<section class="ziifra-documents-panel">
    <div class="ziifra-documents-panel-head">
        <div>
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('project_documents.folders.title') }}</h3>
            <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('project_documents.folders.subtitle') }}</p>
        </div>
    </div>

    <div class="ziifra-documents-panel-body">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <a href="{{ route('project-documents.index', ['view' => 'all']) }}"
                data-page-nav
                class="ziifra-doc-folder group ziifra-doc-folder-muted">
                <span class="ziifra-doc-folder-icon" aria-hidden="true">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75"/>
                    </svg>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="block truncate text-sm font-semibold text-ziifra-ink group-hover:text-ziifra-accent-deep">
                        {{ __('project_documents.all_projects') }}
                    </span>
                    <span class="mt-1 block text-xs leading-relaxed text-ziifra-muted">{{ __('project_documents.all_projects_hint') }}</span>
                    <span class="ziifra-doc-folder-count">{{ __('documents.folders.items', ['count' => $summaryStats['total']]) }}</span>
                </span>
                <span class="ziifra-doc-folder-arrow" aria-hidden="true">→</span>
            </a>

            @foreach ($projects as $index => $project)
                @php
                    $tone = 'ziifra-doc-folder-'.($projectTones[$index % count($projectTones)]);
                @endphp
                <a href="{{ route('project-documents.index', ['project' => $project->id]) }}"
                    data-page-nav
                    @class(['ziifra-doc-folder group', $tone])>
                    <span class="ziifra-doc-folder-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ziifra-ink group-hover:text-ziifra-accent-deep">
                            {{ $project->name }}
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-ziifra-muted">{{ __('project_documents.project_folder_hint') }}</span>
                        <span class="ziifra-doc-folder-count">{{ __('documents.folders.items', ['count' => $project->documents_count]) }}</span>
                    </span>
                    <span class="ziifra-doc-folder-arrow" aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>

        @if ($projects->isEmpty())
            <div class="ziifra-documents-empty mt-4">
                <p class="font-medium text-ziifra-ink">{{ __('project_documents.no_projects') }}</p>
                <p class="mt-1 text-sm text-ziifra-muted">{{ __('project_documents.no_projects_hint') }}</p>
                @if ($canManage)
                    <a href="{{ route('projects.create') }}" data-page-nav class="mt-4 ziifra-btn-primary !text-sm">{{ __('project_documents.create_project') }}</a>
                @endif
            </div>
        @endif
    </div>
</section>

<div class="ziifra-documents-toolbar">
    <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wider text-ziifra-muted">{{ __('project_documents.library_label') }}</p>
        <h2 class="mt-1 text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ __('project_documents.title') }}</h2>
        <p class="mt-1 max-w-2xl text-sm leading-relaxed text-ziifra-muted">{{ __('project_documents.subtitle') }}</p>
    </div>
    @if ($canManage)
        <a href="{{ route('project-documents.index', ['view' => 'all']) }}" data-page-nav class="ziifra-btn-primary !text-sm shrink-0">
            {{ __('project_documents.view_all') }}
        </a>
    @endif
</div>

<div class="ziifra-documents-stats">
    <x-dashboard.stat
        :label="__('project_documents.stats.total')"
        :value="$summaryStats['total']"
        :href="route('project-documents.index', ['view' => 'all'])"
        icon-tone="accent"
        :hint="__('project_documents.stats.total_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('project_documents.stats.projects')"
        :value="$summaryStats['projects']"
        icon-tone="sky"
        :hint="__('project_documents.stats.projects_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('project_documents.stats.this_month')"
        :value="$summaryStats['this_month']"
        icon-tone="amber"
        :hint="__('project_documents.stats.this_month_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('project_documents.stats.categories')"
        :value="$summaryStats['categories']"
        icon-tone="copper"
        :hint="__('project_documents.stats.categories_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>
</div>

<div class="ziifra-documents-toolbar">
    <div class="min-w-0">
        <p class="text-xs font-medium uppercase tracking-wider text-ziifra-muted">{{ __('documents.library_label') }}</p>
        <h2 class="mt-1 text-xl font-semibold tracking-tight text-ziifra-ink sm:text-2xl">{{ __('documents.title') }}</h2>
        <p class="mt-1 max-w-2xl text-sm leading-relaxed text-ziifra-muted">{{ __('documents.index_subtitle') }}</p>
    </div>
</div>

<div class="ziifra-documents-stats">
    <x-dashboard.stat
        :label="__('documents.stats.total')"
        :value="$summaryStats['total']"
        icon-tone="accent"
        :hint="__('documents.stats.total_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('documents.stats.expiring')"
        :value="$summaryStats['expiring']"
        :href="$summaryStats['expiring'] > 0 ? route('documents.index', ['expiry' => 'expiring']) : null"
        icon-tone="amber"
        :variant="$summaryStats['expiring'] > 0 ? 'warn' : 'default'"
        :hint="__('documents.stats.expiring_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('documents.stats.expired')"
        :value="$summaryStats['expired']"
        :href="$summaryStats['expired'] > 0 ? route('documents.index', ['expiry' => 'expired']) : null"
        icon-tone="copper"
        :variant="$summaryStats['expired'] > 0 ? 'alert' : 'default'"
        :hint="__('documents.stats.expired_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>

    <x-dashboard.stat
        :label="__('documents.stats.folders')"
        :value="count($types) + $customFolders->count()"
        icon-tone="sky"
        :hint="__('documents.stats.folders_hint')"
    >
        <x-slot:icon>
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
            </svg>
        </x-slot:icon>
    </x-dashboard.stat>
</div>

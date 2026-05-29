@php
    $folderTones = [
        'contract' => 'ziifra-doc-folder-accent',
        'id_document' => 'ziifra-doc-folder-sky',
        'certificate' => 'ziifra-doc-folder-amber',
        'other' => 'ziifra-doc-folder-muted',
    ];
    $folderIcons = [
        'contract' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>',
        'id_document' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0z"/>',
        'certificate' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 00-.491 6.347A48.62 48.62 0 0112 20.904a48.62 48.62 0 018.232-4.41 60.46 60.46 0 00-.491-6.347m-15.482 0a50.636 50.636 0 00-2.658-.813A59.906 59.906 0 0112 3.493a59.903 59.903 0 0110.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0112 13.489a50.702 50.702 0 017.74-3.342"/>',
        'other' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>',
    ];
@endphp

<section class="ziifra-documents-panel">
    <div class="ziifra-documents-panel-head">
        <div>
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.folders.title') }}</h3>
            <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('documents.folders.subtitle') }}</p>
        </div>
    </div>

    <div class="ziifra-documents-panel-body">
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($types as $docType)
                @php
                    $count = $typeCounts[$docType->value] ?? 0;
                    $tone = $folderTones[$docType->value] ?? 'ziifra-doc-folder-muted';
                @endphp
                <a href="{{ route('documents.index', ['type' => $docType->value]) }}"
                    data-page-nav
                    @class(['ziifra-doc-folder group', $tone])>
                    <span class="ziifra-doc-folder-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            {!! $folderIcons[$docType->value] ?? $folderIcons['other'] !!}
                        </svg>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ziifra-ink group-hover:text-ziifra-accent-deep">
                            {{ __('documents.folders.'.$docType->value) }}
                        </span>
                        <span class="mt-1 block text-xs leading-relaxed text-ziifra-muted">
                            {{ __('documents.folders.'.$docType->value.'_hint') }}
                        </span>
                        <span class="ziifra-doc-folder-count">
                            {{ __('documents.folders.items', ['count' => $count]) }}
                        </span>
                    </span>
                    <span class="ziifra-doc-folder-arrow" aria-hidden="true">→</span>
                </a>
            @endforeach
        </div>
    </div>
</section>

@if ($customFolders->isNotEmpty() || $canManage)
    <section class="ziifra-documents-panel">
        <div class="ziifra-documents-panel-head">
            <div>
                <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.folder_custom_title') }}</h3>
                <p class="mt-0.5 text-sm text-ziifra-muted">{{ __('documents.folder_custom_subtitle') }}</p>
            </div>
        </div>

        <div class="ziifra-documents-panel-body">
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($customFolders as $folder)
                    <a href="{{ route('documents.index', ['folder' => $folder->id]) }}"
                        data-page-nav
                        class="ziifra-doc-folder group ziifra-doc-folder-custom">
                        <span class="ziifra-doc-folder-icon" aria-hidden="true">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                            </svg>
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ziifra-ink group-hover:text-ziifra-accent-deep">
                                {{ $folder->name }}
                            </span>
                            <span class="mt-1 block text-xs leading-relaxed text-ziifra-muted">
                                {{ __('documents.folder_custom_hint') }}
                            </span>
                            <span class="ziifra-doc-folder-count">
                                {{ __('documents.folders.items', ['count' => $folder->documents_count]) }}
                            </span>
                        </span>
                        <span class="ziifra-doc-folder-arrow" aria-hidden="true">→</span>
                    </a>
                @endforeach

                @if ($canManage)
                    <details class="ziifra-doc-folder-create group" @if ($errors->has('name')) open @endif>
                        <summary class="ziifra-doc-folder-create-trigger">
                            <span class="ziifra-doc-folder-icon" aria-hidden="true">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                </svg>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-ziifra-ink">{{ __('documents.create_folder') }}</span>
                                <span class="mt-1 block text-xs text-ziifra-muted">{{ __('documents.create_folder_hint') }}</span>
                            </span>
                        </summary>
                        <form method="POST" action="{{ route('documents.folders.store') }}" class="ziifra-doc-folder-create-form">
                            @csrf
                            <label for="folder_name" class="sr-only">{{ __('documents.folder_name') }}</label>
                            <input type="text" id="folder_name" name="name" required maxlength="100"
                                value="{{ old('name') }}"
                                placeholder="{{ __('documents.folder_name_placeholder') }}"
                                class="ziifra-documents-field px-3">
                            @error('name')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                            <button type="submit" class="ziifra-btn-primary mt-3 w-full !text-sm">
                                {{ __('documents.create_folder') }}
                            </button>
                        </form>
                    </details>
                @endif
            </div>
        </div>
    </section>
@endif

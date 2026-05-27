@php
    $folderLabel = $selectedFolder?->name ?? $selectedTypeEnum?->label() ?? __('documents.filtered_view');
    $folderHint = $selectedFolder
        ? __('documents.folder_custom_hint')
        : ($selectedTypeEnum
            ? __('documents.folders.'.$selectedTypeEnum->value.'_hint')
            : __('documents.index_subtitle'));
    $folderTone = $selectedFolder
        ? 'ziifra-doc-folder-custom'
        : match ($selectedType) {
            'contract' => 'ziifra-doc-folder-accent',
            'id_document' => 'ziifra-doc-folder-sky',
            'certificate' => 'ziifra-doc-folder-amber',
            default => 'ziifra-doc-folder-muted',
        };
@endphp

<div class="ziifra-documents-folder-head">
    <div class="flex min-w-0 flex-1 items-start gap-4">
        <span @class(['ziifra-documents-folder-icon', $folderTone]) aria-hidden="true">
            @if ($selectedFolder)
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z"/>
                </svg>
            @else
                <svg class="h-6 w-6" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M4 5a2 2 0 012-2h4.586a1 1 0 01.707.293l1.414 1.414A1 1 0 0013.414 6H18a2 2 0 012 2v11a2 2 0 01-2 2H6a2 2 0 01-2-2V5z"/>
                </svg>
            @endif
        </span>
        <div class="min-w-0">
            <h2 class="truncate text-lg font-semibold tracking-tight text-ziifra-ink sm:text-xl">{{ $folderLabel }}</h2>
            <p class="mt-1 text-sm text-ziifra-muted">{{ $folderHint }}</p>
            @if ($fileCount !== null)
                <p class="mt-2 inline-flex items-center gap-1.5 rounded-full bg-ziifra-cream/80 px-2.5 py-0.5 text-xs font-medium tabular-nums text-ziifra-muted ring-1 ring-ziifra-line/70">
                    {{ __('documents.folders.items', ['count' => $fileCount]) }}
                </p>
            @endif
        </div>
    </div>

    <div class="flex shrink-0 flex-wrap items-center gap-2">
        <a href="{{ route('documents.index') }}" data-page-nav class="ziifra-btn-app-outline !text-sm">
            {{ __('documents.back_to_library') }}
        </a>
        @if ($canManage)
            <a href="#document-upload" class="ziifra-btn-primary !text-sm">
                {{ __('documents.upload') }}
            </a>
        @endif
        @if ($selectedFolder && $canManage && ($selectedFolder->documents_count ?? 0) === 0)
            <form method="POST" action="{{ route('documents.folders.destroy', $selectedFolder) }}"
                data-confirm="{{ __('documents.confirm_folder_delete', ['name' => $selectedFolder->name]) }}"
                data-confirm-variant="danger"
                data-confirm-accept="{{ __('common.delete') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-lg px-3 py-2 text-sm font-medium text-red-600 hover:bg-red-50">
                    {{ __('documents.folder_delete') }}
                </button>
            </form>
        @endif
    </div>
</div>

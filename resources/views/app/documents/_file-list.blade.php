@php
    $fileIconTone = function (string $filename): string {
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

        return match ($ext) {
            'pdf' => 'pdf',
            'jpg', 'jpeg', 'png', 'webp' => 'image',
            'doc', 'docx' => 'doc',
            default => 'file',
        };
    };
@endphp

<div class="ziifra-documents-panel">
    @if ($documents->isEmpty())
        <div class="ziifra-documents-empty">
            <span class="ziifra-documents-empty-icon" aria-hidden="true">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                </svg>
            </span>
            <p class="font-medium text-ziifra-ink">
                @if (($selectedTypeEnum || $selectedFolder) && ! request()->hasAny(['search', 'employee_id', 'expiry']))
                    {{ __('documents.empty_type') }}
                @else
                    {{ __('documents.index_empty') }}
                @endif
            </p>
            @if ($canManage)
                <a href="#document-upload" class="mt-4 ziifra-btn-primary !text-sm">{{ __('documents.upload') }}</a>
            @endif
        </div>
    @else
        <div class="space-y-3 p-3 md:hidden">
            @foreach ($documents as $document)
                @php
                    $initials = collect(explode(' ', trim($document->employee->fullName())))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                    $iconTone = $fileIconTone($document->original_filename);
                @endphp
                <article class="ziifra-documents-file-card">
                    <div class="flex items-start gap-3">
                        <span @class(['ziifra-documents-file-icon', 'ziifra-documents-file-icon--'.$iconTone]) aria-hidden="true">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                            </svg>
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold text-ziifra-ink">{{ $document->title }}</p>
                            <p class="mt-0.5 truncate text-xs text-ziifra-muted">{{ $document->original_filename }}</p>
                            <div class="mt-3 flex items-center gap-2">
                                <span class="ziifra-documents-avatar">{{ $initials }}</span>
                                <a href="{{ route('employees.show', $document->employee) }}" data-page-nav class="truncate text-sm font-medium text-ziifra-accent-deep hover:underline">
                                    {{ $document->employee->fullName() }}
                                </a>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center gap-2 text-xs">
                                @if (! $selectedTypeEnum && ! $selectedFolder)
                                    <span class="ziifra-documents-badge">{{ $document->type->label() }}</span>
                                @endif
                                @if ($document->expires_at)
                                    <span @class([
                                        'ziifra-documents-badge',
                                        'ziifra-documents-badge--danger' => $document->isExpired(),
                                        'ziifra-documents-badge--warn' => ! $document->isExpired() && $document->isExpiringSoon(),
                                    ])>
                                        @if ($document->isExpired())
                                            {{ __('documents.expired', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @elseif ($document->isExpiringSoon())
                                            {{ __('documents.expiring_soon', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @else
                                            {{ __('documents.expires', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @endif
                                    </span>
                                @endif
                                <span class="text-ziifra-muted">{{ $document->created_at->format('M j, Y') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="mt-4 flex items-center gap-3 border-t border-ziifra-line/60 pt-3">
                        <a href="{{ route('employees.documents.download', [$document->employee, $document]) }}"
                            class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                            {{ __('documents.download') }}
                        </a>
                        @if ($canManage)
                            <form method="POST"
                                action="{{ route('employees.documents.destroy', [$document->employee, $document]) }}"
                                data-confirm="{{ __('documents.confirm_delete') }}"
                                data-confirm-variant="danger"
                                data-confirm-accept="{{ __('common.delete') }}"
                                class="ml-auto">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="redirect" value="documents">
                                @foreach ($filterQuery as $key => $value)
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                @endforeach
                                <button type="submit" class="text-sm text-red-600 hover:underline">
                                    {{ __('documents.remove') }}
                                </button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
        @if ($documents->hasPages())
            <div class="border-t border-ziifra-line/60 px-4 py-3 md:hidden">{{ $documents->links() }}</div>
        @endif

        <div class="ziifra-table-scroll hidden md:block">
            <table class="ziifra-documents-table">
                <thead>
                    <tr>
                        <th>{{ __('documents.column_title') }}</th>
                        <th>{{ __('documents.employee') }}</th>
                        @if (! $selectedTypeEnum && ! $selectedFolder)
                            <th>{{ __('documents.type') }}</th>
                        @endif
                        <th>{{ __('documents.column_expiry') }}</th>
                        <th>{{ __('documents.column_uploaded') }}</th>
                        <th class="text-right">{{ __('documents.column_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($documents as $document)
                        @php
                            $initials = collect(explode(' ', trim($document->employee->fullName())))->filter()->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');
                            $iconTone = $fileIconTone($document->original_filename);
                        @endphp
                        <tr>
                            <td>
                                <div class="flex items-start gap-3">
                                    <span @class(['ziifra-documents-file-icon', 'ziifra-documents-file-icon--'.$iconTone]) aria-hidden="true">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                    </span>
                                    <div class="min-w-0">
                                        <p class="font-medium text-ziifra-ink">{{ $document->title }}</p>
                                        <p class="truncate text-xs text-ziifra-muted">{{ $document->original_filename }}</p>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="ziifra-documents-avatar">{{ $initials }}</span>
                                    <div class="min-w-0">
                                        <a href="{{ route('employees.show', $document->employee) }}" data-page-nav
                                            class="font-medium text-ziifra-accent-deep hover:underline">
                                            {{ $document->employee->fullName() }}
                                        </a>
                                        @if ($document->employee->department)
                                            <p class="truncate text-xs text-ziifra-muted">{{ $document->employee->department->name }}</p>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            @if (! $selectedTypeEnum && ! $selectedFolder)
                                <td><span class="ziifra-documents-badge">{{ $document->type->label() }}</span></td>
                            @endif
                            <td>
                                @if ($document->expires_at)
                                    <span @class([
                                        'ziifra-documents-badge',
                                        'ziifra-documents-badge--danger' => $document->isExpired(),
                                        'ziifra-documents-badge--warn' => ! $document->isExpired() && $document->isExpiringSoon(),
                                    ])>
                                        @if ($document->isExpired())
                                            {{ __('documents.expired', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @elseif ($document->isExpiringSoon())
                                            {{ __('documents.expiring_soon', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @else
                                            {{ __('documents.expires', ['date' => $document->expires_at->format('M j, Y')]) }}
                                        @endif
                                    </span>
                                @else
                                    <span class="text-xs text-ziifra-muted">—</span>
                                @endif
                            </td>
                            <td class="text-ziifra-muted">
                                <span class="text-sm">{{ $document->created_at->format('M j, Y') }}</span>
                                @if ($document->uploadedBy)
                                    <span class="block text-xs">{{ $document->uploadedBy->name }}</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('employees.documents.download', [$document->employee, $document]) }}"
                                        class="ziifra-documents-action">
                                        {{ __('documents.download') }}
                                    </a>
                                    @if ($canManage)
                                        <form method="POST"
                                            action="{{ route('employees.documents.destroy', [$document->employee, $document]) }}"
                                            data-confirm="{{ __('documents.confirm_delete') }}"
                                            data-confirm-variant="danger"
                                            data-confirm-accept="{{ __('common.delete') }}"
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect" value="documents">
                                            @foreach ($filterQuery as $key => $value)
                                                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                                            @endforeach
                                            <button type="submit" class="ziifra-documents-action ziifra-documents-action--danger">
                                                {{ __('documents.remove') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if ($documents->hasPages())
            <div class="border-t border-ziifra-line/60 px-4 py-3 hidden md:block">
                {{ $documents->links() }}
            </div>
        @endif
    @endif
</div>

@php
    $embedded = $embedded ?? false;
@endphp

<section @class(['ziifra-dashboard-panel' => $embedded, 'mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6' => ! $embedded])>
    @if ($embedded)
        <div class="ziifra-dashboard-panel-head">
            <div>
                <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.title') }}</h2>
                @if ($employee->documents->isNotEmpty())
                    <p class="text-xs text-ziifra-muted">{{ trans_choice('employees.documents_count', $employee->documents->count(), ['count' => $employee->documents->count()]) }}</p>
                @endif
            </div>
            @if ($employee->documents->isNotEmpty())
                <span class="ziifra-dashboard-badge">{{ $employee->documents->count() }}</span>
            @endif
        </div>
        <div class="p-4 sm:p-5">
    @else
        <h3 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.title') }}</h3>
    @endif

    @if ($employee->documents->isEmpty())
        <p @class(['text-sm text-ziifra-muted', 'mt-4' => ! $embedded])>{{ __('documents.empty') }}</p>
    @else
        <ul @class(['space-y-2', 'mt-4' => ! $embedded])>
            @foreach ($employee->documents as $document)
                <li class="ziifra-employee-doc">
                    <div class="ziifra-employee-doc-icon" aria-hidden="true">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.75">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                    </div>
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ziifra-ink">{{ $document->title }}</p>
                        <p class="mt-0.5 text-xs text-ziifra-muted">
                            {{ $document->type->label() }}
                            @if ($document->uploadedBy)
                                · {{ $document->uploadedBy->name }}
                            @endif
                            · {{ $document->created_at->format('M j, Y') }}
                        </p>
                        @if ($document->expires_at)
                            <p @class([
                                'mt-1 inline-flex rounded-full px-2 py-0.5 text-[0.65rem] font-medium',
                                $document->isExpired() ? 'bg-red-50 text-red-700' : ($document->isExpiringSoon() ? 'bg-amber-50 text-amber-800' : 'bg-ziifra-cream text-ziifra-muted'),
                            ])>
                                @if ($document->isExpired())
                                    {{ __('documents.expired', ['date' => $document->expires_at->format('M j, Y')]) }}
                                @elseif ($document->isExpiringSoon())
                                    {{ __('documents.expiring_soon', ['date' => $document->expires_at->format('M j, Y')]) }}
                                @else
                                    {{ __('documents.expires', ['date' => $document->expires_at->format('M j, Y')]) }}
                                @endif
                            </p>
                        @endif
                        @if ($document->notes)
                            <p class="mt-1 text-sm text-ziifra-muted">{{ $document->notes }}</p>
                        @endif
                    </div>
                    <div class="ziifra-employee-doc-actions">
                        <a href="{{ route('employees.documents.download', [$employee, $document]) }}"
                            class="ziifra-btn-app-outline !px-3 !py-1.5 !text-xs">
                            {{ __('documents.download') }}
                        </a>
                        @if ($canManage)
                            <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $document]) }}"
                                data-confirm="Remove this document?"
                                data-confirm-variant="danger"
                                data-confirm-accept="{{ __('common.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ziifra-employee-profile-danger-btn !px-3 !py-1.5 !text-xs">{{ __('common.remove') }}</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canManage)
        <details class="ziifra-employee-doc-upload mt-4 border-t border-ziifra-line/60 pt-4">
            <summary class="cursor-pointer text-sm font-medium text-ziifra-accent-deep hover:underline">{{ __('documents.upload') }}</summary>
            <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data" class="mt-4 space-y-4">
                @csrf
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="document_type" class="ziifra-label-field">{{ __('documents.type') }}</label>
                        <select id="document_type" name="type" required class="ziifra-input mt-1 !py-2 !text-sm">
                            @foreach (\App\Enums\EmployeeDocumentType::cases() as $docType)
                                <option value="{{ $docType->value }}" @selected(old('type') === $docType->value)>{{ $docType->label() }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="document_title" class="ziifra-label-field">{{ __('documents.document_title') }}</label>
                        <input type="text" id="document_title" name="title" value="{{ old('title') }}" required maxlength="255"
                            class="ziifra-input mt-1 !py-2 !text-sm"
                            placeholder="e.g. Employment contract 2026">
                        @error('title')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="document_file" class="ziifra-label-field">{{ __('documents.file') }}</label>
                        <input type="file" id="document_file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                            class="mt-1 w-full text-sm text-ziifra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-3 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
                        @error('file')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="document_expires_at" class="ziifra-label-field">{{ __('documents.expires_at') }}</label>
                        <input type="date" id="document_expires_at" name="expires_at" value="{{ old('expires_at') }}"
                            class="ziifra-input mt-1 !py-2 !text-sm">
                        @error('expires_at')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="sm:col-span-2">
                        <label for="document_notes" class="ziifra-label-field">{{ __('documents.notes') }}</label>
                        <textarea id="document_notes" name="notes" rows="2" maxlength="2000"
                            class="ziifra-input mt-1 !py-2 !text-sm">{{ old('notes') }}</textarea>
                        @error('notes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <button type="submit" class="ziifra-btn-primary !py-2 !text-sm">
                    {{ __('documents.upload') }}
                </button>
            </form>
        </details>
    @endif
    @if ($embedded)
        </div>
    @endif
</section>

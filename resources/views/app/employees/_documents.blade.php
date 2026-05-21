@php
    $embedded = $embedded ?? false;
@endphp

<section @class(['ziifra-dashboard-panel' => $embedded, 'mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6' => ! $embedded])>
    @if ($embedded)
        <div class="ziifra-dashboard-panel-head">
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.title') }}</h2>
        </div>
        <div class="p-5">
    @else
        <h3 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.title') }}</h3>
    @endif

    @if ($employee->documents->isEmpty())
        <p @class(['text-sm text-ziifra-muted', 'mt-4' => ! $embedded])>{{ __('documents.empty') }}</p>
    @else
        <ul class="mt-4 divide-y divide-ziifra-line/60">
            @foreach ($employee->documents as $document)
                <li class="flex flex-wrap items-start justify-between gap-3 py-3">
                    <div class="min-w-0 flex-1">
                        <p class="font-medium text-ziifra-ink">{{ $document->title }}</p>
                        <p class="text-xs text-ziifra-muted">
                            {{ $document->type->label() }}
                            @if ($document->uploadedBy)
                                · {{ $document->uploadedBy->name }}
                            @endif
                            · {{ $document->created_at->format('M j, Y') }}
                        </p>
                        @if ($document->expires_at)
                            <p @class([
                                'mt-1 text-xs font-medium',
                                $document->isExpired() ? 'text-red-700' : ($document->isExpiringSoon() ? 'text-amber-700' : 'text-ziifra-muted'),
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
                    <div class="flex shrink-0 items-center gap-2">
                        <a href="{{ route('employees.documents.download', [$employee, $document]) }}"
                            class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                            {{ $document->original_filename }}
                        </a>
                        @if ($canManage)
                            <form method="POST" action="{{ route('employees.documents.destroy', [$employee, $document]) }}"
                                data-confirm="Remove this document?"
                                data-confirm-variant="danger"
                                data-confirm-accept="{{ __('common.remove') }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm text-red-600 hover:underline">Remove</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif

    @if ($canManage)
        <form method="POST" action="{{ route('employees.documents.store', $employee) }}" enctype="multipart/form-data"
            class="mt-6 space-y-4 border-t border-ziifra-line/60 pt-6">
            @csrf
            <p class="text-sm font-medium text-ziifra-ink">{{ __('documents.upload') }}</p>
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label for="document_type" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.type') }}</label>
                    <select id="document_type" name="type" required
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        @foreach (\App\Enums\EmployeeDocumentType::cases() as $docType)
                            <option value="{{ $docType->value }}" @selected(old('type') === $docType->value)>{{ $docType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="document_title" class="block text-sm font-medium text-ziifra-muted">Title</label>
                    <input type="text" id="document_title" name="title" value="{{ old('title') }}" required maxlength="255"
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm"
                        placeholder="e.g. Employment contract 2026">
                    @error('title')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="document_file" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.file') }}</label>
                    <input type="file" id="document_file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                        class="mt-1 w-full text-sm text-ziifra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-3 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
                    @error('file')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="document_expires_at" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.expires_at') }}</label>
                    <input type="date" id="document_expires_at" name="expires_at" value="{{ old('expires_at') }}"
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    @error('expires_at')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div class="sm:col-span-2">
                    <label for="document_notes" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.notes') }}</label>
                    <textarea id="document_notes" name="notes" rows="2" maxlength="2000"
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">{{ old('notes') }}</textarea>
                    @error('notes')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-white hover:bg-ziifra-accent-deep">
                {{ __('documents.upload') }}
            </button>
        </form>
    @endif
    @if ($embedded)
        </div>
    @endif
</section>

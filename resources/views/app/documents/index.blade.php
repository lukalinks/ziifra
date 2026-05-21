@extends('layouts.app')

@section('title', __('documents.title'))
@section('header', __('documents.title'))

@section('content')
<p class="text-sm text-ziifra-muted">{{ __('documents.index_subtitle') }}</p>

<div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
    <form method="GET" action="{{ route('documents.index') }}" class="flex flex-wrap items-end gap-3">
        <div>
            <label for="search" class="sr-only">{{ __('documents.search') }}</label>
            <input type="search" id="search" name="search" value="{{ request('search') }}"
                placeholder="{{ __('documents.search_placeholder') }}"
                class="w-full min-w-[12rem] rounded-lg border border-ziifra-line px-3 py-2 text-sm sm:w-48">
        </div>
        <div>
            <label for="employee_id" class="sr-only">{{ __('documents.employee') }}</label>
            <select id="employee_id" name="employee_id" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">{{ __('documents.all_employees') }}</option>
                @foreach ($employees as $employee)
                    <option value="{{ $employee->id }}" @selected(request('employee_id') == $employee->id)>
                        {{ $employee->fullName() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="type" class="sr-only">{{ __('documents.type') }}</label>
            <select id="type" name="type" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">{{ __('documents.all_types') }}</option>
                @foreach ($types as $docType)
                    <option value="{{ $docType->value }}" @selected(request('type') === $docType->value)>
                        {{ $docType->label() }}
                    </option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="expiry" class="sr-only">{{ __('documents.expiry_filter') }}</label>
            <select id="expiry" name="expiry" class="rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                <option value="">{{ __('documents.all_expiry') }}</option>
                <option value="expiring" @selected(request('expiry') === 'expiring')>{{ __('documents.filter_expiring') }}</option>
                <option value="expired" @selected(request('expiry') === 'expired')>{{ __('documents.filter_expired') }}</option>
                <option value="none" @selected(request('expiry') === 'none')>{{ __('documents.filter_no_expiry') }}</option>
            </select>
        </div>
        <button type="submit" class="rounded-lg border border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-ink hover:bg-ziifra-cream">
            {{ __('documents.filter') }}
        </button>
        @if (request()->hasAny(['search', 'employee_id', 'type', 'expiry']))
            <a href="{{ route('documents.index') }}" class="rounded-lg px-3 py-2 text-sm text-ziifra-muted hover:text-ziifra-ink">
                {{ __('documents.clear_filters') }}
            </a>
        @endif
    </form>
</div>

<div class="mt-6 overflow-hidden rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
    @if ($documents->isEmpty())
        <p class="p-8 text-center text-sm text-ziifra-muted">{{ __('documents.index_empty') }}</p>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-ziifra-line/60 text-sm">
                <thead class="bg-ziifra-cream/50 text-left text-xs font-medium uppercase tracking-wider text-ziifra-muted">
                    <tr>
                        <th class="px-4 py-3">{{ __('documents.column_title') }}</th>
                        <th class="px-4 py-3">{{ __('documents.employee') }}</th>
                        <th class="px-4 py-3">{{ __('documents.type') }}</th>
                        <th class="px-4 py-3">{{ __('documents.column_expiry') }}</th>
                        <th class="px-4 py-3">{{ __('documents.column_uploaded') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('documents.column_actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ziifra-line/60">
                    @foreach ($documents as $document)
                        <tr class="hover:bg-ziifra-cream/30">
                            <td class="px-4 py-3">
                                <p class="font-medium text-ziifra-ink">{{ $document->title }}</p>
                                <p class="text-xs text-ziifra-muted">{{ $document->original_filename }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('employees.show', $document->employee) }}"
                                    class="font-medium text-ziifra-accent-deep hover:underline">
                                    {{ $document->employee->fullName() }}
                                </a>
                                @if ($document->employee->department)
                                    <p class="text-xs text-ziifra-muted">{{ $document->employee->department->name }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-ziifra-muted">{{ $document->type->label() }}</td>
                            <td class="px-4 py-3">
                                @if ($document->expires_at)
                                    <span @class([
                                        'text-xs font-medium',
                                        $document->isExpired() ? 'text-red-700' : ($document->isExpiringSoon() ? 'text-amber-700' : 'text-ziifra-muted'),
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
                            <td class="px-4 py-3 text-ziifra-muted">
                                {{ $document->created_at->format('M j, Y') }}
                                @if ($document->uploadedBy)
                                    <span class="block text-xs">{{ $document->uploadedBy->name }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
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
                                            class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="redirect" value="documents">
                                            <button type="submit" class="text-sm text-red-600 hover:underline">
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
            <div class="border-t border-ziifra-line/60 px-4 py-3">
                {{ $documents->links() }}
            </div>
        @endif
    @endif
</div>

@if (count($contractTemplates) > 0)
    <section class="mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.templates.title') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('documents.templates.subtitle') }}</p>
        @if ($canManageOrganization ?? false)
            <p class="mt-2">
                <a href="{{ route('settings.contract-templates.index') }}" class="text-sm font-medium text-ziifra-accent-deep hover:underline">
                    {{ __('documents.templates.settings.manage_link') }}
                </a>
            </p>
        @endif

        <div class="mt-6 grid gap-4 lg:grid-cols-2">
            @foreach ($contractTemplates as $contractTemplate)
                <article class="rounded-xl border border-ziifra-line/70 bg-ziifra-paper/40 p-4">
                    <h3 class="text-sm font-semibold text-ziifra-ink">{{ $contractTemplate->name }}</h3>
                    <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ $contractTemplate->description }}</p>

                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="{{ route('documents.templates.download', $contractTemplate) }}"
                            class="rounded-lg border border-ziifra-line px-3 py-1.5 text-xs font-medium text-ziifra-ink hover:bg-ziifra-paper">
                            {{ __('documents.templates.download_blank') }}
                        </a>
                    </div>

                    @if ($canManage)
                        <form method="POST" action="{{ route('documents.templates.generate', $contractTemplate) }}"
                            class="mt-4 space-y-3 border-t border-ziifra-line/60 pt-4">
                            @csrf
                            <div>
                                <label for="employee_{{ $contractTemplate->slug }}" class="block text-xs font-medium text-ziifra-muted">
                                    {{ __('documents.templates.select_employee') }}
                                </label>
                                <select id="employee_{{ $contractTemplate->slug }}" name="employee_id" required
                                    class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                                    <option value="" disabled selected>{{ __('documents.select_employee') }}</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <label class="flex items-center gap-2 text-xs text-ziifra-muted">
                                <input type="checkbox" name="save_to_documents" value="1" class="rounded border-ziifra-line">
                                {{ __('documents.templates.save_to_documents') }}
                            </label>
                            <button type="submit" class="rounded-lg bg-ziifra-accent px-3 py-1.5 text-xs font-semibold text-ziifra-ink hover:bg-ziifra-accent-glow">
                                {{ __('documents.templates.generate') }}
                            </button>
                        </form>
                    @endif
                </article>
            @endforeach
        </div>
    </section>
@endif

@if ($canManage)
    <section class="mt-8 rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-6">
        <h2 class="text-lg font-semibold text-ziifra-ink">{{ __('documents.upload') }}</h2>
        <p class="mt-1 text-sm text-ziifra-muted">{{ __('documents.upload_from_index_hint') }}</p>

        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data"
            class="mt-6 space-y-4 border-t border-ziifra-line/60 pt-6">
            @csrf
            <div class="grid gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="employee_id_upload" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.employee') }}</label>
                    <select id="employee_id_upload" name="employee_id" required
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        <option value="" disabled @selected(! old('employee_id'))>{{ __('documents.select_employee') }}</option>
                        @foreach ($employees as $employee)
                            <option value="{{ $employee->id }}" @selected(old('employee_id') == $employee->id)>
                                {{ $employee->fullName() }}
                            </option>
                        @endforeach
                    </select>
                    @error('employee_id')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="document_type" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.type') }}</label>
                    <select id="document_type" name="type" required
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                        @foreach ($types as $docType)
                            <option value="{{ $docType->value }}" @selected(old('type') === $docType->value)>{{ $docType->label() }}</option>
                        @endforeach
                    </select>
                    @error('type')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="document_title" class="block text-sm font-medium text-ziifra-muted">{{ __('documents.column_title') }}</label>
                    <input type="text" id="document_title" name="title" value="{{ old('title') }}" required maxlength="255"
                        class="mt-1 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm"
                        placeholder="{{ __('documents.title_placeholder') }}">
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
            <button type="submit" class="rounded-lg bg-ziifra-accent px-4 py-2 text-sm font-semibold text-ziifra-ink hover:bg-ziifra-accent-glow">
                {{ __('documents.upload') }}
            </button>
        </form>
    </section>
@endif
@endsection

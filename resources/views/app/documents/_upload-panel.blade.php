<section id="document-upload" class="ziifra-documents-side-card">
    <div class="ziifra-documents-side-card-head">
        <div>
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('documents.upload') }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ __('documents.upload_from_index_hint') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="ziifra-documents-side-card-body space-y-4">
        @csrf
        @if ($selectedFolder)
            <input type="hidden" name="document_folder_id" value="{{ $selectedFolder->id }}">
        @endif

        <div>
            <label for="employee_id_upload" class="ziifra-documents-label">{{ __('documents.employee') }}</label>
            <select id="employee_id_upload" name="employee_id" required
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
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
            <label for="document_type" class="ziifra-documents-label">{{ __('documents.type') }}</label>
            <select id="document_type" name="type" required
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
                @foreach ($types as $docType)
                    <option value="{{ $docType->value }}" @selected(old('type', $selectedType ?? \App\Enums\EmployeeDocumentType::Other->value) === $docType->value)>
                        {{ $docType->label() }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="document_title" class="ziifra-documents-label">{{ __('documents.column_title') }}</label>
            <input type="text" id="document_title" name="title" value="{{ old('title') }}" required maxlength="255"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm"
                placeholder="{{ __('documents.title_placeholder') }}">
            @error('title')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="document_file" class="ziifra-documents-label">{{ __('documents.file') }}</label>
            <input type="file" id="document_file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx"
                class="mt-1.5 w-full rounded-lg border border-dashed border-ziifra-line bg-ziifra-cream/30 px-3 py-3 text-sm text-ziifra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ziifra-paper file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ziifra-ink">
            @error('file')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="document_expires_at" class="ziifra-documents-label">{{ __('documents.expires_at') }}</label>
            <input type="date" id="document_expires_at" name="expires_at" value="{{ old('expires_at') }}"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
            @error('expires_at')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="document_notes" class="ziifra-documents-label">{{ __('documents.notes') }}</label>
            <textarea id="document_notes" name="notes" rows="2" maxlength="2000"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">{{ old('notes') }}</textarea>
            @error('notes')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="ziifra-btn-primary w-full !text-sm">
            {{ __('documents.upload') }}
        </button>
    </form>
</section>

<section id="project-document-upload" class="ziifra-documents-side-card">
    <div class="ziifra-documents-side-card-head">
        <div>
            <h2 class="text-sm font-semibold text-ziifra-ink">{{ __('project_documents.upload') }}</h2>
            <p class="mt-1 text-xs leading-relaxed text-ziifra-muted">{{ __('project_documents.upload_hint') }}</p>
        </div>
    </div>

    <form method="POST" action="{{ route('project-documents.store') }}" enctype="multipart/form-data" class="ziifra-documents-side-card-body space-y-4">
        @csrf
        <div>
            <label for="project_id_upload" class="ziifra-documents-label">{{ __('project_documents.project') }}</label>
            <select id="project_id_upload" name="project_id" required class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
                @foreach ($projects as $project)
                    <option value="{{ $project->id }}" @selected(old('project_id', $selectedProject?->id) == $project->id)>{{ $project->name }}</option>
                @endforeach
            </select>
            @error('project_id')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="project_doc_category" class="ziifra-documents-label">{{ __('project_documents.category') }}</label>
            <select id="project_doc_category" name="category" required class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
                @foreach ($categories as $category)
                    <option value="{{ $category->value }}" @selected(old('category') === $category->value)>{{ $category->label() }}</option>
                @endforeach
            </select>
            @error('category')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="project_doc_amount" class="ziifra-documents-label">{{ __('project_documents.field_amount') }}</label>
            <input type="number" id="project_doc_amount" name="amount" value="{{ old('amount') }}" min="0" step="0.01"
                placeholder="0.00"
                class="ziifra-documents-field mt-1.5 w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('project_documents.field_amount_hint') }}</p>
            @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="project_doc_title" class="ziifra-documents-label">{{ __('documents.column_title') }}</label>
            <input type="text" id="project_doc_title" name="title" value="{{ old('title') }}" required maxlength="255"
                placeholder="{{ __('project_documents.title_placeholder') }}"
                class="mt-1.5 w-full rounded-lg border border-ziifra-line bg-white px-3 py-2 text-sm">
            @error('title')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="project_doc_file" class="ziifra-documents-label">{{ __('documents.file') }}</label>
            <input type="file" id="project_doc_file" name="file" required accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                class="mt-1.5 w-full rounded-lg border border-dashed border-ziifra-line bg-ziifra-cream/30 px-3 py-3 text-sm text-ziifra-muted file:mr-3 file:rounded-lg file:border-0 file:bg-ziifra-paper file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-ziifra-ink">
            @error('file')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <button type="submit" class="ziifra-btn-primary w-full !text-sm">{{ __('project_documents.upload') }}</button>
    </form>
</section>

<div class="space-y-4">
    @if ($canManage)
        <form method="POST" action="{{ route('projects.documents.store', $project) }}" enctype="multipart/form-data" class="rounded-xl border border-ziifra-line/80 bg-ziifra-paper p-4">
            @csrf
            <input type="hidden" name="from_project" value="1">
            <input type="hidden" name="project_id" value="{{ $project->id }}">
            <div class="grid gap-4 sm:grid-cols-2">
                <div>
                    <label class="ziifra-label-field">{{ __('project_documents.field_title') }}</label>
                    <input type="text" name="title" required class="ziifra-input mt-1 w-full">
                </div>
                <div>
                    <label class="ziifra-label-field">{{ __('project_documents.field_category') }}</label>
                    <select name="category" required class="ziifra-input mt-1 w-full">
                        @foreach ($documentCategories as $category)
                            <option value="{{ $category->value }}">{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ziifra-label-field">{{ __('project_documents.field_amount') }}</label>
                    <input type="number" name="amount" min="0" step="0.01" class="ziifra-input mt-1 w-full" placeholder="0.00">
                    <p class="mt-1 text-xs text-ziifra-muted">{{ __('project_documents.field_amount_hint') }}</p>
                </div>
                <div class="sm:col-span-2">
                    <label class="ziifra-label-field">{{ __('project_documents.field_file') }}</label>
                    <input type="file" name="file" required class="mt-1 block w-full text-sm">
                </div>
            </div>
            <button type="submit" class="ziifra-btn-app mt-4 !text-sm">{{ __('project_documents.upload') }}</button>
        </form>
    @endif

    @if ($project->documents->isEmpty())
        <p class="text-sm text-ziifra-muted">{{ __('project_documents.empty') }}</p>
    @else
        <ul class="divide-y divide-ziifra-line/60 rounded-xl border border-ziifra-line/80 bg-ziifra-paper">
            @foreach ($project->documents as $doc)
                <li class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 text-sm">
                    <div>
                        <p class="font-medium text-ziifra-ink">{{ $doc->title }}</p>
                        <p class="text-xs text-ziifra-muted">
                            {{ $doc->category->label() }} · {{ $doc->uploaded_at?->format('M j, Y') }}
                            @if ($doc->amount !== null)
                                · {{ number_format((float) $doc->amount, 2, '.', '\'') }} {{ $project->currency ?? 'EUR' }}
                            @endif
                        </p>
                    </div>
                    <div class="flex gap-2">
                        <a href="{{ route('projects.documents.download', ['project' => $project, 'projectDocument' => $doc]) }}" class="ziifra-btn-app-outline !py-1.5 !text-xs">{{ __('common.download') }}</a>
                        @if ($canManage)
                            <form method="POST" action="{{ route('projects.documents.destroy', ['project' => $project, 'projectDocument' => $doc]) }}">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="from_project" value="1">
                                <button type="submit" class="ziifra-btn-app-outline !py-1.5 !text-xs text-red-700">{{ __('common.delete') }}</button>
                            </form>
                        @endif
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

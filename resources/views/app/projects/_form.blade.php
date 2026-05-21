<div class="space-y-5">
    <div>
        <label for="name" class="mb-1 block text-sm font-medium">{{ __('projects.name') }}</label>
        <input type="text" name="name" id="name" value="{{ old('name', $project->name ?? '') }}" required
            class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="description" class="mb-1 block text-sm font-medium">{{ __('projects.description') }}</label>
        <textarea name="description" id="description" rows="4"
            class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">{{ old('description', $project->description ?? '') }}</textarea>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="status" class="mb-1 block text-sm font-medium">{{ __('projects.status') }}</label>
            <select name="status" id="status" required class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $project?->status?->value ?? \App\Enums\ProjectStatus::Planning->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label for="budget" class="mb-1 block text-sm font-medium">{{ __('projects.budget') }}</label>
            <input type="number" step="0.01" min="0" name="budget" id="budget" value="{{ old('budget', $project->budget ?? '') }}"
                class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        </div>
    </div>
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label for="start_date" class="mb-1 block text-sm font-medium">{{ __('projects.start_date') }}</label>
            <input type="date" name="start_date" id="start_date" value="{{ old('start_date', isset($project) && $project->start_date ? $project->start_date->format('Y-m-d') : '') }}"
                class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        </div>
        <div>
            <label for="end_date" class="mb-1 block text-sm font-medium">{{ __('projects.end_date') }}</label>
            <input type="date" name="end_date" id="end_date" value="{{ old('end_date', isset($project) && $project->end_date ? $project->end_date->format('Y-m-d') : '') }}"
                class="w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
        </div>
    </div>
    <div>
        <p class="mb-2 text-sm font-medium">{{ __('projects.team') }}</p>
        <div class="max-h-48 space-y-2 overflow-y-auto rounded-lg border border-ziifra-line/80 p-3">
            @foreach ($employees as $employee)
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="employee_ids[]" value="{{ $employee->id }}"
                        @checked(in_array($employee->id, old('employee_ids', isset($project) ? $project->members->pluck('id')->all() : []), true))>
                    <span>{{ $employee->fullName() }}</span>
                </label>
            @endforeach
        </div>
    </div>
</div>

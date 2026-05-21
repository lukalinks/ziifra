@php
    $row = $row ?? [];
    $rowType = $row['type'] ?? 'text';
@endphp
<div data-new-field-row class="rounded-lg border border-ziifra-line/80 bg-ziifra-cream/40 p-4">
    <div class="grid gap-4 sm:grid-cols-2">
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">Field name</label>
            <input type="text" name="new_custom_fields[{{ $index }}][name]" value="{{ $row['name'] ?? '' }}"
                placeholder="e.g. Contract PDF"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error("new_custom_fields.{$index}.name")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-ziifra-ink">Field type</label>
            <select name="new_custom_fields[{{ $index }}][type]" data-field-type-select
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                @foreach ($fieldTypes as $type)
                    <option value="{{ $type->value }}" @selected($rowType === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
            @error("new_custom_fields.{$index}.type")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div data-options-wrap class="{{ $rowType === 'select' ? '' : 'hidden' }} sm:col-span-2">
            <label class="block text-sm font-medium text-ziifra-ink">Dropdown options (comma-separated)</label>
            <input type="text" name="new_custom_fields[{{ $index }}][options]" value="{{ $row['options'] ?? '' }}"
                placeholder="Option A, Option B, Option C"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error("new_custom_fields.{$index}.options")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div data-value-wrap class="{{ in_array($rowType, ['select', 'file'], true) ? 'hidden' : '' }}">
            <label class="block text-sm font-medium text-ziifra-ink">Value for this employee</label>
            <input type="text" name="new_custom_fields[{{ $index }}][value]" value="{{ $row['value'] ?? '' }}"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @error("new_custom_fields.{$index}.value")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div data-file-wrap class="{{ $rowType === 'file' ? '' : 'hidden' }} sm:col-span-2">
            <label class="block text-sm font-medium text-ziifra-ink">Upload file</label>
            <input type="file" name="new_custom_fields[{{ $index }}][file]"
                accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
                class="mt-1 block w-full text-sm text-ziifra-muted file:mr-4 file:rounded-lg file:border-0 file:bg-ziifra-paper file:px-4 file:py-2 file:text-sm">
            <p class="mt-1 text-xs text-ziifra-muted">PDF, images, Word, or Excel · max 10 MB</p>
            @error("new_custom_fields.{$index}.file")<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex items-end justify-between sm:col-span-2">
            <label class="flex items-center gap-2 text-sm text-ziifra-muted">
                <input type="checkbox" name="new_custom_fields[{{ $index }}][is_required]" value="1"
                    @checked(!empty($row['is_required']))
                    class="rounded border-ziifra-line text-ziifra-accent">
                Required for all employees
            </label>
            <button type="button" data-remove-new-field class="text-sm text-red-600 hover:text-red-700">Remove</button>
        </div>
    </div>
</div>

@php
    $prefix = $prefix ?? 'custom_fields';
    $index = $index;
    $definition = $definition ?? null;
    $type = $type ?? $definition?->type;
    $existing = $existing ?? null;
    $rawValue = $rawValue ?? old("{$prefix}.{$index}.value", $existing?->value);
    $isFile = $type === \App\Enums\CustomFieldType::File || ($type?->value ?? $type) === 'file';
    $fileMeta = $isFile && $existing?->value ? \App\Support\EmployeeCustomFieldFile::decode($existing->value) : null;
@endphp

@if ($isFile)
    @if ($fileMeta && ($employee ?? null))
        <p class="mt-2 text-sm text-ziifra-muted">
            Current file:
            <a href="{{ route('employees.custom-fields.download', [$employee, $definition]) }}" class="font-medium text-ziifra-accent-deep hover:underline">
                {{ $fileMeta['name'] }}
            </a>
        </p>
        <label class="mt-2 flex items-center gap-2 text-sm text-ziifra-muted">
            <input type="checkbox" name="{{ $prefix }}[{{ $index }}][remove_file]" value="1" class="rounded border-ziifra-line text-ziifra-accent">
            Remove current file
        </label>
    @endif
    <input type="file" name="{{ $prefix }}[{{ $index }}][file]"
        accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,.xls,.xlsx"
        class="mt-2 block w-full text-sm text-ziifra-muted file:mr-4 file:rounded-lg file:border-0 file:bg-ziifra-cream file:px-4 file:py-2 file:text-sm file:font-medium file:text-ziifra-ink">
    <p class="mt-1 text-xs text-ziifra-muted">PDF, images, Word, or Excel · max 10 MB</p>
    @error("{$prefix}.{$index}.file")
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
@elseif (($type?->value ?? $type) === 'select' || $definition?->type === \App\Enums\CustomFieldType::Select)
    <select id="cf_{{ $index }}" name="{{ $prefix }}[{{ $index }}][value]"
        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
        @if($required ?? false) required @endif>
        <option value="">— Select —</option>
        @foreach ($options ?? $definition?->options ?? [] as $option)
            <option value="{{ $option }}" @selected((string) $rawValue === (string) $option)>{{ $option }}</option>
        @endforeach
    </select>
    @error("{$prefix}.{$index}.value")
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
@else
    <input id="cf_{{ $index }}" name="{{ $prefix }}[{{ $index }}][value]"
        type="{{ is_object($type) ? $type->htmlInputType() : 'text' }}"
        value="{{ $rawValue }}"
        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
        @if($required ?? false) required @endif>
    @error("{$prefix}.{$index}.value")
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
@endif

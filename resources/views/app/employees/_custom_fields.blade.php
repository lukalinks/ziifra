@php
    $employee = $employee ?? null;
    $fieldDefinitions = $fieldDefinitions ?? collect();
    $fieldTypes = $fieldTypes ?? \App\Enums\CustomFieldType::cases();
@endphp

<div class="mt-10 border-t border-ziifra-line/80 pt-8">
    <div class="flex items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-ziifra-ink">{{ __('employees.section_custom_fields') }}</h3>
            <p class="mt-1 text-sm text-ziifra-muted">
                @can('create', App\Models\EmployeeFieldDefinition::class)
                    {{ __('employees.custom_fields_intro_admin') }}
                @else
                    {{ __('employees.custom_fields_intro_hr') }}
                @endcan
            </p>
        </div>
    </div>

    @if ($fieldDefinitions->isNotEmpty())
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
            @foreach ($fieldDefinitions as $definition)
                @php
                    $existing = $employee?->customFieldValueFor($definition);
                    $rawValue = old("custom_fields.{$definition->id}.value", $existing?->value);
                @endphp
                <div class="{{ $definition->type === \App\Enums\CustomFieldType::Boolean || $definition->type === \App\Enums\CustomFieldType::File ? 'sm:col-span-2' : '' }}">
                    <input type="hidden" name="custom_fields[{{ $definition->id }}][definition_id]" value="{{ $definition->id }}">

                    @if ($definition->type === \App\Enums\CustomFieldType::Boolean)
                        <label class="flex items-center gap-2 text-sm font-medium text-ziifra-ink">
                            <input type="hidden" name="custom_fields[{{ $definition->id }}][value]" value="0">
                            <input type="checkbox" name="custom_fields[{{ $definition->id }}][value]" value="1"
                                @checked(filter_var($rawValue, FILTER_VALIDATE_BOOLEAN))
                                class="rounded border-ziifra-line text-ziifra-accent">
                            {{ $definition->name }}
                            @if ($definition->is_required)<span class="text-red-600">*</span>@endif
                        </label>
                    @else
                        <label class="block text-sm font-medium text-ziifra-ink">
                            {{ $definition->name }}
                            @if ($definition->is_required)<span class="text-red-600">*</span>@endif
                        </label>
                        @include('app.employees._custom_field_input', [
                            'prefix' => 'custom_fields',
                            'index' => $definition->id,
                            'definition' => $definition,
                            'type' => $definition->type,
                            'existing' => $existing,
                            'employee' => $employee,
                            'required' => $definition->is_required,
                        ])
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    @can('create', App\Models\EmployeeFieldDefinition::class)
        <div class="mt-8">
            <h4 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.add_custom_field_heading') }}</h4>
            <p class="mt-1 text-sm text-ziifra-muted">{{ __('employees.add_custom_field_help') }}</p>

            <div id="new-custom-fields" class="mt-4 space-y-4">
                @php
                    $newRows = old('new_custom_fields', [[]]);
                @endphp
                @foreach ($newRows as $index => $row)
                    @include('app.employees._new_custom_field_row', ['index' => $index, 'row' => $row, 'fieldTypes' => $fieldTypes])
                @endforeach
            </div>

            <button type="button" id="add-custom-field-row"
                class="mt-4 rounded-lg border border-dashed border-ziifra-line px-4 py-2 text-sm font-medium text-ziifra-accent-deep hover:border-ziifra-accent hover:bg-ziifra-cream">
                {{ __('employees.add_another_custom_field') }}
            </button>
        </div>
    @endcan
</div>

@can('create', App\Models\EmployeeFieldDefinition::class)
    <template id="new-custom-field-template">
        @include('app.employees._new_custom_field_row', ['index' => '__INDEX__', 'row' => [], 'fieldTypes' => $fieldTypes])
    </template>

    <script>
        (function () {
            const container = document.getElementById('new-custom-fields');
            const template = document.getElementById('new-custom-field-template');
            const addBtn = document.getElementById('add-custom-field-row');
            if (!container || !template || !addBtn) return;

            let nextIndex = container.querySelectorAll('[data-new-field-row]').length;

            function toggleRowType(row) {
                const type = row.querySelector('[data-field-type-select]')?.value;
                const optionsWrap = row.querySelector('[data-options-wrap]');
                const valueWrap = row.querySelector('[data-value-wrap]');
                const fileWrap = row.querySelector('[data-file-wrap]');
                if (optionsWrap) optionsWrap.classList.toggle('hidden', type !== 'select');
                if (valueWrap) valueWrap.classList.toggle('hidden', type === 'select' || type === 'file');
                if (fileWrap) fileWrap.classList.toggle('hidden', type !== 'file');
            }

            container.querySelectorAll('[data-new-field-row]').forEach(toggleRowType);

            addBtn.addEventListener('click', function () {
                const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex));
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html;
                const row = wrapper.firstElementChild;
                container.appendChild(row);
                toggleRowType(row);
                nextIndex++;
            });

            container.addEventListener('click', function (e) {
                if (e.target.matches('[data-remove-new-field]')) {
                    e.target.closest('[data-new-field-row]')?.remove();
                }
            });

            container.addEventListener('change', function (e) {
                if (e.target.matches('[data-field-type-select]')) {
                    toggleRowType(e.target.closest('[data-new-field-row]'));
                }
            });
        })();
    </script>
@endcan

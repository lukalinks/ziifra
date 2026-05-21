@php
    $taxCases = \App\Enums\AllowanceTaxTreatment::cases();
    $oldTpl = old('allowance_templates');

    if (is_array($oldTpl)) {
        $tplRows = collect(array_values($oldTpl))->map(fn ($r) => is_array($r) ? $r : [])->values();
    } elseif (isset($allowanceTemplateRows) && $allowanceTemplateRows instanceof \Illuminate\Support\Collection && $allowanceTemplateRows->isNotEmpty()) {
        $tplRows = $allowanceTemplateRows->map(fn ($a) => [
            'label' => $a->label,
            'amount' => $a->amount,
            'tax_treatment' => $a->tax_treatment->value,
        ])->values();
    } else {
        $tplRows = collect([['label' => '', 'amount' => '', 'tax_treatment' => 'taxable']]);
    }
@endphp
<div class="sm:col-span-2 rounded-xl border border-ziifra-line/80 bg-ziifra-cream/30 p-4">
    <p class="text-sm font-medium text-ziifra-ink">{{ __('employees.allowance_templates_heading') }}</p>
    <p class="mt-1 text-xs text-ziifra-muted">{{ __('employees.allowance_templates_help') }}</p>
    <div class="employee-allowance-templates mt-4 space-y-3"
        data-employee-allowance-templates
        data-next-index="{{ $tplRows->count() }}">
        <div class="employee-allowance-template-rows space-y-3">
            @foreach ($tplRows as $idx => $row)
                <div class="employee-allowance-template-row grid gap-2 rounded-lg border border-ziifra-line/60 bg-ziifra-paper p-3 sm:grid-cols-12 sm:items-end">
                    <div class="sm:col-span-5">
                        <label class="block text-xs font-medium text-ziifra-muted">{{ __('employees.allowance_label_field') }}</label>
                        <input type="text" name="allowance_templates[{{ $idx }}][label]"
                            value="{{ $row['label'] ?? '' }}"
                            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-medium text-ziifra-muted">{{ __('employees.allowance_amount_field') }}</label>
                        <input type="number" step="0.01" min="0" name="allowance_templates[{{ $idx }}][amount]"
                            value="{{ $row['amount'] ?? '' }}"
                            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm"
                            placeholder="0.00">
                    </div>
                    <div class="sm:col-span-4">
                        <label class="block text-xs font-medium text-ziifra-muted">{{ __('employees.allowance_tax_treatment_field') }}</label>
                        <select name="allowance_templates[{{ $idx }}][tax_treatment]" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2 text-sm">
                            @foreach ($taxCases as $case)
                                <option value="{{ $case->value }}" @selected(($row['tax_treatment'] ?? 'taxable') === $case->value)>
                                    {{ $case->label() }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button"
            data-add-employee-allowance-template
            class="text-xs font-medium text-ziifra-accent-deep hover:underline">
            + {{ __('employees.add_allowance_template_row') }}
        </button>
    </div>
    <p class="mt-3 text-xs text-ziifra-muted">{{ __('employees.field_monthly_allowances_legacy_note') }}</p>
</div>
<div>
    <label for="monthly_allowances" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_monthly_allowances') }}</label>
    <input id="monthly_allowances" name="monthly_allowances" type="number" step="0.01" min="0"
        value="{{ old('monthly_allowances', $employee?->monthly_allowances ?? 0) }}"
        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
        placeholder="0.00">
    <p class="mt-1 text-xs text-ziifra-muted">{{ __('employees.field_monthly_allowances_help') }}</p>
    @error('monthly_allowances')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
</div>

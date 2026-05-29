@php
    use App\Enums\CompensationType;

    $employee = $employee ?? null;
    $orgCurrency = \App\Support\CurrentOrganization::get()?->currency ?? 'EUR';
    $currencyOptions = array_values(array_unique(['EUR', 'CHF', 'USD', $orgCurrency]));
    $compType = old('compensation_type', $employee?->compensation_type?->value);
    $hourlyCurrency = old('fixed_hourly_currency', $employee?->fixed_hourly_currency) ?: $orgCurrency;
    $salaryCurrency = old('fixed_salary_currency', $employee?->fixed_salary_currency) ?: $orgCurrency;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="first_name" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_first_name') }}</label>
        <input id="first_name" name="first_name" type="text"
            value="{{ old('first_name', $employee?->first_name) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="last_name" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_last_name') }}</label>
        <input id="last_name" name="last_name" type="text"
            value="{{ old('last_name', $employee?->last_name) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="employee_code" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_employee_code') }}</label>
        <input id="employee_code" name="employee_code" type="text" maxlength="50"
            value="{{ old('employee_code', $employee?->employee_code) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('employee_code')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_email') }}</label>
        <input id="email" name="email" type="email"
            value="{{ old('email', $employee?->email) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_phone') }}</label>
        <input id="phone" name="phone" type="text"
            value="{{ old('phone', $employee?->phone) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="department_id" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_department') }}</label>
        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">{{ __('employees.option_none') }}</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $employee?->department_id) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="position_id" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_position') }}</label>
        <select id="position_id" name="position_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">{{ __('employees.option_none') }}</option>
            @foreach ($positions as $position)
                <option value="{{ $position->id }}" @selected(old('position_id', $employee?->position_id) == $position->id)>
                    {{ $position->title }}
                </option>
            @endforeach
        </select>
        @error('position_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="manager_id" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_manager') }}</label>
        <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">{{ __('employees.option_none') }}</option>
            @foreach ($managers as $manager)
                @if ($employee && $manager->id === $employee->id)
                    @continue
                @endif
                <option value="{{ $manager->id }}" @selected(old('manager_id', $employee?->manager_id) == $manager->id)>
                    {{ $manager->fullName() }}
                </option>
            @endforeach
        </select>
        @error('manager_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @if (isset($linkableUsers))
        <div class="sm:col-span-2">
            <label for="user_id" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_linked_login') }}</label>
            <select id="user_id" name="user_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                <option value="">{{ __('employees.option_none') }}</option>
                @foreach ($linkableUsers as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $employee?->user_id) == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
                @if ($employee?->user_id && $employee->user && ! $linkableUsers->contains('id', $employee->user_id))
                    <option value="{{ $employee->user_id }}" selected>{{ $employee->user->name }} ({{ $employee->user->email }})</option>
                @endif
            </select>
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('employees.field_linked_login_help') }}</p>
            @error('user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif
    @if (($projects ?? collect())->isNotEmpty())
        <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-ziifra-ink">{{ __('employees.project_assignments') }}</label>
            <p class="mt-0.5 text-xs text-ziifra-muted">{{ __('employees.project_assignments_hint') }}</p>
            <div class="mt-2 max-h-48 space-y-2 overflow-y-auto rounded-lg border border-ziifra-line/80 p-3">
                @foreach ($projects as $project)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="project_ids[]" value="{{ $project->id }}"
                            @checked(collect(old('project_ids', $employee?->projects?->pluck('id')->all() ?? []))->contains($project->id))>
                        {{ $project->name }}
                    </label>
                @endforeach
            </div>
            @error('project_ids')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif
    <div>
        <label for="employment_type" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_employment_type') }}</label>
        <select id="employment_type" name="employment_type" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('employment_type', $employee?->employment_type?->value ?? ($defaultEmploymentType ?? 'full_time')) === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('employment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="employment_status" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_employment_status') }}</label>
        <select id="employment_status" name="employment_status" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('employment_status', $employee?->employment_status?->value ?? 'active') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('employment_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="start_date" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_start_date') }}</label>
        <input id="start_date" name="start_date" type="date"
            value="{{ old('start_date', $employee?->start_date?->format('Y-m-d')) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @if ($showPayrollFields ?? false)
        <div class="sm:col-span-2 mt-2 rounded-xl border border-ziifra-line/80 bg-ziifra-surface/40 p-4">
            <h3 class="text-sm font-semibold text-ziifra-ink">{{ __('employees.section_payroll') }}</h3>
            <p class="mt-0.5 text-xs text-ziifra-muted">{{ __('employees.payroll_hint') }}</p>

            <div class="mt-3">
                <label for="compensation_type" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.compensation_type') }}</label>
                <select id="compensation_type" name="compensation_type" data-compensation-type class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                    <option value="">{{ __('employees.compensation_none') }}</option>
                    <option value="{{ CompensationType::Hourly->value }}" @selected($compType === CompensationType::Hourly->value)>{{ __('employees.compensation_hourly') }}</option>
                    <option value="{{ CompensationType::Monthly->value }}" @selected($compType === CompensationType::Monthly->value)>{{ __('employees.compensation_monthly') }}</option>
                </select>
                @error('compensation_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>

            <div class="mt-3 grid gap-4 sm:grid-cols-2" data-compensation-hourly @if($compType !== CompensationType::Hourly->value) hidden @endif>
                <div>
                    <label for="fixed_hourly_rate" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.fixed_hourly_rate') }}</label>
                    <input id="fixed_hourly_rate" name="fixed_hourly_rate" type="number" step="0.01" min="0"
                        value="{{ old('fixed_hourly_rate', $employee?->fixed_hourly_rate) }}"
                        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2" placeholder="0.00">
                    @error('fixed_hourly_rate')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="fixed_hourly_currency" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.currency') }}</label>
                    <select id="fixed_hourly_currency" name="fixed_hourly_currency" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                        @foreach ($currencyOptions as $cur)
                            <option value="{{ $cur }}" @selected($hourlyCurrency === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3 grid gap-4 sm:grid-cols-2" data-compensation-monthly @if($compType !== CompensationType::Monthly->value) hidden @endif>
                <div>
                    <label for="fixed_monthly_salary" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.fixed_monthly_salary') }}</label>
                    <input id="fixed_monthly_salary" name="fixed_monthly_salary" type="number" step="0.01" min="0"
                        value="{{ old('fixed_monthly_salary', $employee?->fixed_monthly_salary) }}"
                        class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2" placeholder="0.00">
                    @error('fixed_monthly_salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="fixed_salary_currency" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.currency') }}</label>
                    <select id="fixed_salary_currency" name="fixed_salary_currency" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                        @foreach ($currencyOptions as $cur)
                            <option value="{{ $cur }}" @selected($salaryCurrency === $cur)>{{ $cur }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-4 border-t border-ziifra-line/60 pt-4">
                <label for="gross_salary" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_gross_salary') }}</label>
                <input id="gross_salary" name="gross_salary" type="number" step="0.01" min="0"
                    value="{{ old('gross_salary', $employee?->gross_salary) }}"
                    class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                    placeholder="0.00">
                <p class="mt-1 text-xs text-ziifra-muted">{{ __('employees.field_gross_salary_help') }}</p>
                @error('gross_salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
            </div>
            @include('app.employees._allowance_templates')
        </div>
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-compensation-type]').forEach((select) => {
                const root = select.closest('.grid') ?? document;
                const hourly = root.querySelector('[data-compensation-hourly]');
                const monthly = root.querySelector('[data-compensation-monthly]');
                const sync = () => {
                    if (hourly) hourly.hidden = select.value !== 'hourly';
                    if (monthly) monthly.hidden = select.value !== 'monthly';
                };
                select.addEventListener('change', sync);
                sync();
            });
        </script>
    @endpush
@endonce

<p class="mt-4 text-sm text-ziifra-muted">
    <a href="{{ route('settings.departments.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('employees.manage_departments') }}</a>
    ·
    <a href="{{ route('settings.positions.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('employees.manage_positions') }}</a>
    @can('create', App\Models\EmployeeFieldDefinition::class)
        ·
        <a href="{{ route('settings.employee-fields.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">{{ __('employees.manage_custom_fields') }}</a>
    @endcan
</p>

@include('app.employees._custom_fields')

@php
    $employee = $employee ?? null;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div>
        <label for="first_name" class="block text-sm font-medium text-ziifra-ink">First name</label>
        <input id="first_name" name="first_name" type="text" required
            value="{{ old('first_name', $employee?->first_name) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('first_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="last_name" class="block text-sm font-medium text-ziifra-ink">Last name</label>
        <input id="last_name" name="last_name" type="text" required
            value="{{ old('last_name', $employee?->last_name) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('last_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="email" class="block text-sm font-medium text-ziifra-ink">Email</label>
        <input id="email" name="email" type="email"
            value="{{ old('email', $employee?->email) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('email')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="phone" class="block text-sm font-medium text-ziifra-ink">Phone</label>
        <input id="phone" name="phone" type="text"
            value="{{ old('phone', $employee?->phone) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('phone')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="department_id" class="block text-sm font-medium text-ziifra-ink">Department</label>
        <select id="department_id" name="department_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">— None —</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}" @selected(old('department_id', $employee?->department_id) == $department->id)>
                    {{ $department->name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="position_id" class="block text-sm font-medium text-ziifra-ink">Position</label>
        <select id="position_id" name="position_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">— None —</option>
            @foreach ($positions as $position)
                <option value="{{ $position->id }}" @selected(old('position_id', $employee?->position_id) == $position->id)>
                    {{ $position->title }}
                </option>
            @endforeach
        </select>
        @error('position_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div class="sm:col-span-2">
        <label for="manager_id" class="block text-sm font-medium text-ziifra-ink">Manager</label>
        <select id="manager_id" name="manager_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            <option value="">— None —</option>
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
            <label for="user_id" class="block text-sm font-medium text-ziifra-ink">Linked login account</label>
            <select id="user_id" name="user_id" class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
                <option value="">— None —</option>
                @foreach ($linkableUsers as $user)
                    <option value="{{ $user->id }}" @selected(old('user_id', $employee?->user_id) == $user->id)>
                        {{ $user->name }} ({{ $user->email }})
                    </option>
                @endforeach
                @if ($employee?->user_id && $employee->user && ! $linkableUsers->contains('id', $employee->user_id))
                    <option value="{{ $employee->user_id }}" selected>{{ $employee->user->name }} ({{ $employee->user->email }})</option>
                @endif
            </select>
            <p class="mt-1 text-xs text-ziifra-muted">Links a team member so they can request their own leave.</p>
            @error('user_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif
    <div>
        <label for="employment_type" class="block text-sm font-medium text-ziifra-ink">Employment type</label>
        <select id="employment_type" name="employment_type" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('employment_type', $employee?->employment_type?->value ?? ($defaultEmploymentType ?? 'full_time')) === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        @error('employment_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="employment_status" class="block text-sm font-medium text-ziifra-ink">Employment status</label>
        <select id="employment_status" name="employment_status" required class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(old('employment_status', $employee?->employment_status?->value ?? 'active') === $status->value)>
                    {{ $status->label() }}
                </option>
            @endforeach
        </select>
        @error('employment_status')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    <div>
        <label for="start_date" class="block text-sm font-medium text-ziifra-ink">Start date</label>
        <input id="start_date" name="start_date" type="date"
            value="{{ old('start_date', $employee?->start_date?->format('Y-m-d')) }}"
            class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2">
        @error('start_date')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
    @if ($showPayrollFields ?? false)
        <div>
            <label for="gross_salary" class="block text-sm font-medium text-ziifra-ink">{{ __('employees.field_gross_salary') }}</label>
            <input id="gross_salary" name="gross_salary" type="number" step="0.01" min="0"
                value="{{ old('gross_salary', $employee?->gross_salary) }}"
                class="mt-1 block w-full rounded-lg border border-ziifra-line px-3 py-2"
                placeholder="0.00">
            <p class="mt-1 text-xs text-ziifra-muted">{{ __('employees.field_gross_salary_help') }}</p>
            @error('gross_salary')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
        @include('app.employees._allowance_templates')
    @endif
</div>

<p class="mt-4 text-sm text-ziifra-muted">
    <a href="{{ route('settings.departments.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">Manage departments</a>
    ·
    <a href="{{ route('settings.positions.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">Manage positions</a>
    @can('create', App\Models\EmployeeFieldDefinition::class)
        ·
        <a href="{{ route('settings.employee-fields.index') }}" class="text-ziifra-accent-deep hover:text-ziifra-accent-deep">Manage custom fields</a>
    @endcan
</p>

@include('app.employees._custom_fields')

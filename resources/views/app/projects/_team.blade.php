@php
    $memberIds = $project->members->pluck('id');
    $assignable = ($employees ?? collect())->reject(fn ($e) => $memberIds->contains($e->id));
@endphp
<div class="p-3 sm:p-4">
    @if ($canManage)
        <form method="POST" action="{{ route('projects.members.store', $project) }}" class="mb-5 flex flex-wrap items-end gap-2">
            @csrf
            <label class="min-w-[14rem] flex-1">
                <span class="mb-1 block text-xs font-medium text-ziifra-muted">{{ __('projects.add_member') }}</span>
                <select name="employee_id" class="ziifra-input !text-sm" required @disabled($assignable->isEmpty())>
                    <option value="">{{ __('projects.select_member') }}</option>
                    @foreach ($assignable as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->fullName() }}</option>
                    @endforeach
                </select>
            </label>
            <button type="submit" class="ziifra-btn-app !text-sm" @disabled($assignable->isEmpty())>{{ __('projects.add_member_action') }}</button>
        </form>
        @if ($assignable->isEmpty() && $project->members->isNotEmpty())
            <p class="mb-4 text-xs text-ziifra-muted">{{ __('projects.all_members_added') }}</p>
        @endif
    @endif

    @if ($project->members->isEmpty())
        <div class="ziifra-dashboard-empty py-10">
            <span class="ziifra-dashboard-empty-icon text-sky-500/70">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
            </span>
            <p class="mt-3 font-medium text-ziifra-ink">{{ __('daily_hours.no_employees') }}</p>
            <p class="mt-1 text-sm text-ziifra-muted">{{ __('daily_hours.no_employees_hint') }}</p>
        </div>
    @else
        <div class="ziifra-employees-compact-grid">
            @foreach ($project->members as $member)
                <article class="ziifra-employee-compact-card">
                    <a href="{{ route('employees.show', $member) }}" class="ziifra-employee-compact-card-main" data-page-nav>
                        <span class="ziifra-employee-compact-card-avatar" aria-hidden="true">{{ $member->initials() }}</span>
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-ziifra-ink">{{ $member->fullName() }}</span>
                            <span class="mt-0.5 block truncate text-xs text-ziifra-muted">{{ $member->position?->title ?? $member->displayCode() }}</span>
                        </span>
                    </a>
                    <div class="ziifra-employee-compact-card-actions">
                        <a href="{{ route('employees.show', $member) }}" class="ziifra-employee-compact-card-link" data-page-nav>{{ __('employees.view') }}</a>
                        @if ($canManage)
                            <form method="POST" action="{{ route('projects.members.destroy', [$project, $member]) }}"
                                onsubmit="return confirm('{{ __('projects.remove_member_confirm') }}');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="ziifra-employee-compact-card-link !text-red-600">{{ __('projects.remove_member') }}</button>
                            </form>
                        @endif
                    </div>
                </article>
            @endforeach
        </div>
    @endif
</div>

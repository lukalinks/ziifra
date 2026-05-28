<span data-employees-count class="sr-only">{{ __('employees.count', ['count' => $employees->total()]) }}</span>

<div class="ziifra-employees-index-panel-head md:hidden">
    <p class="text-sm font-medium text-ziifra-ink">{{ __('employees.count', ['count' => $employees->total()]) }}</p>
</div>

@if ($employees->isEmpty())
    <div class="ziifra-dashboard-empty py-12">
        <span class="ziifra-dashboard-empty-icon text-sky-500/70">
            <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
        </span>
        <p class="mt-3 font-medium text-ziifra-ink">{{ __('employees.empty') }}</p>
        @if ($canManage)
            <a href="{{ route('employees.create') }}" class="ziifra-btn-primary mt-4 !text-sm" data-page-nav>{{ __('employees.empty_action') }}</a>
        @endif
    </div>
@else
    <div class="ziifra-table-scroll p-3 sm:p-4 md:p-0 md:px-0">
        <table class="ziifra-table min-w-full text-sm">
            <thead class="bg-ziifra-cream/50 text-left text-xs font-semibold uppercase tracking-wide text-ziifra-muted">
                <tr>
                    <th class="px-4 py-2.5">{{ __('employees.name') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_employee_code') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_email') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_department') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_position') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_type') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.field_status') }}</th>
                    <th class="px-4 py-2.5">{{ __('employees.login') }}</th>
                    <th class="px-4 py-2.5 text-right"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-ziifra-line/60">
                @foreach ($employees as $employee)
                    @include('app.employees._index-table-row', [
                        'employee' => $employee,
                        'pendingLoginInvites' => $pendingLoginInvites,
                        'canManage' => $canManage,
                    ])
                @endforeach
            </tbody>
        </table>
    </div>

    @if ($employees->hasPages())
        <div class="border-t border-ziifra-line/80 px-4 py-3 sm:px-5">
            {{ $employees->links() }}
        </div>
    @endif
@endif

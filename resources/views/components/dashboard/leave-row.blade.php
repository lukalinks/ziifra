@props(['request', 'badge' => null])

<a href="{{ route('leave.show', $request) }}" {{ $attributes->class(['ziifra-dashboard-leave-row group']) }}>
    <span class="ziifra-dashboard-avatar">{{ $request->employee->initials() }}</span>
    <span class="min-w-0 flex-1">
        <span class="block truncate font-medium text-ziifra-ink group-hover:text-ziifra-accent-deep">
            {{ $request->employee->fullName() }}
        </span>
        <span class="block truncate text-xs text-ziifra-muted">{{ $request->leaveType->name }}</span>
    </span>
    @if ($badge)
        <span class="shrink-0 text-right text-xs text-ziifra-muted">{{ $badge }}</span>
    @elseif (trim($slot) !== '')
        <span class="shrink-0">{{ $slot }}</span>
    @endif
</a>

@props(['logs', 'platform'])

<div class="overflow-hidden rounded-xl border border-slate-200 bg-ziifra-paper">
    <div class="ziifra-table-scroll">
    <table class="min-w-full divide-y divide-slate-200 text-sm">
        <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
                <th class="px-4 py-3 font-medium">{{ __('admin.audit.action') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('admin.audit.admin') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('admin.audit.organization') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('admin.audit.target') }}</th>
                <th class="px-4 py-3 font-medium">{{ __('admin.audit.when') }}</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($logs as $log)
                <tr>
                    <td class="px-4 py-3 font-medium text-slate-900">{{ $platform->actionLabel($log->action) }}</td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->admin?->email ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-600">
                        @if ($log->organization)
                            <a href="{{ route('admin.organizations.show', $log->organization) }}" class="text-indigo-600 hover:text-indigo-700">
                                {{ $log->organization->name }}
                            </a>
                        @else
                            —
                        @endif
                    </td>
                    <td class="px-4 py-3 text-slate-600">{{ $log->targetUser?->email ?? '—' }}</td>
                    <td class="px-4 py-3 text-slate-500" title="{{ $log->created_at }}">
                        {{ $log->created_at->diffForHumans() }}
                        @if ($log->ip_address)
                            <span class="block text-xs text-slate-400">{{ $log->ip_address }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="px-4 py-8 text-center text-slate-500">No activity recorded yet.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>
</div>
